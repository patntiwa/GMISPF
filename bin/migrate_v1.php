<?php
declare(strict_types=1);

/**
 * Migration utility: imports legacy GMISPF V1 MySQL dump files into GMISPF V2 SQLite database.
 * Usage:
 *   php bin/migrate_v1.php <dump.sql> [--owner-id=ID] [--import-users] [--dry-run]
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';

$args = array_slice($argv, 1);
$file = null;
$ownerId = null;
$importUsers = false;
$dryRun = false;

foreach ($args as $arg) {
    if ($arg === '--dry-run') {
        $dryRun = true;
    } elseif ($arg === '--import-users') {
        $importUsers = true;
    } elseif (str_starts_with($arg, '--owner-id=')) {
        $ownerId = (int) substr($arg, 11);
    } elseif (!str_starts_with($arg, '--') && $file === null) {
        $file = $arg;
    }
}

if (!$file || !is_file($file)) {
    fwrite(STDERR, "Usage: php bin/migrate_v1.php <dump.sql> [--owner-id=USER_ID] [--import-users] [--dry-run]\n");
    exit(1);
}

$db = db();
$content = file_get_contents($file);
if ($content === false) {
    fwrite(STDERR, "Erreur de lecture du fichier.\n");
    exit(1);
}

echo "=== Migration GMISPF V1 -> V2 ===\n";
echo "Fichier source : " . basename($file) . "\n";

// 1. Optional user import
if ($importUsers) {
    echo "\n[1/2] Analyse des utilisateurs V1...\n";
    $userPattern = "/INSERT INTO `?utilisateur`? \([^)]+\) VALUES\s*([^;]+);/i";
    if (preg_match_all($userPattern, $content, $uMatches)) {
        $usersFound = [];
        foreach ($uMatches[1] as $block) {
            preg_match_all("/\(([^)]+)\)/", $block, $tMatches);
            foreach ($tMatches[1] as $tuple) {
                $fields = str_getcsv($tuple, ',', "'");
                $fields = array_map(static fn($v) => trim((string)$v), $fields);
                if (count($fields) < 5) continue;
                // Iduser, Username, Email, Fonction, mdp
                $name = trim($fields[1] ?: 'Utilisateur V1');
                $rawEmail = strtolower(trim($fields[2]));
                // Fix common typo from dump e.g. "patntiwa19@gmailcom" -> "patntiwa19@gmail.com"
                if (!str_contains($rawEmail, '.') && str_contains($rawEmail, '@')) {
                    $rawEmail = preg_replace('/@([a-z0-9]+)$/', '@$1.com', $rawEmail);
                }
                $role = (strcasecmp($fields[3], 'Agent') === 0) ? 'agent' : 'prestataire';
                $usersFound[] = ['name' => $name, 'email' => $rawEmail, 'role' => $role];
            }
        }
        echo count($usersFound) . " utilisateur(s) trouvé(s) dans le dump.\n";
        if (!$dryRun) {
            foreach ($usersFound as $u) {
                $exists = $db->prepare("SELECT id FROM users WHERE email = ?");
                $exists->execute([$u['email']]);
                if ($exists->fetch()) {
                    echo " - Ignoré (déjà existant) : {$u['email']}\n";
                    continue;
                }
                $tempPassword = bin2hex(random_bytes(10));
                $hash = password_hash($tempPassword, PASSWORD_DEFAULT, ['cost' => 12]);
                $stmt = $db->prepare("INSERT INTO users (name, email, role, password_hash, active) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$u['name'], $u['email'], $u['role'], $hash]);
                $newUid = $db->lastInsertId();
                echo " + Utilisateur créé (#{$newUid}) : {$u['name']} <{$u['email']}> [{$u['role']}]\n";
                echo "   Mot de passe temporaire généré : {$tempPassword}\n";
            }
        }
    } else {
        echo "Aucune instruction INSERT INTO `utilisateur` détectée.\n";
    }
}

// 2. Report import
echo "\n[2/2] Analyse des rapports et interventions V1...\n";

if ($ownerId === null) {
    $firstUser = $db->query("SELECT id, name, role FROM users WHERE role IN ('prestataire', 'admin') ORDER BY id ASC LIMIT 1")->fetch();
    if (!$firstUser) {
        fwrite(STDERR, "Erreur : aucun utilisateur éligible dans la base V2. Créez d’abord un compte avec bin/console.php user:create.\n");
        exit(1);
    }
    $ownerId = (int) $firstUser['id'];
} else {
    $owner = $db->prepare("SELECT id, name FROM users WHERE id = ?");
    $owner->execute([$ownerId]);
    if (!$owner->fetch()) {
        fwrite(STDERR, "Erreur : utilisateur #{$ownerId} introuvable.\n");
        exit(1);
    }
}
echo "Propriétaire attribué aux rapports : ID #{$ownerId}\n";

$reportPattern = "/INSERT INTO `?(?:rapport|document|historique)`? \([^)]+\) VALUES\s*([^;]+);/i";
preg_match_all($reportPattern, $content, $rMatches);

$rowsToInsert = [];
$normalizeCategory = static function (string $raw): string {
    $rawLower = mb_strtolower(trim($raw), 'UTF-8');
    if (str_contains($rawLower, 'forage')) return 'Forage';
    if (str_contains($rawLower, 'sanitaire')) return 'Sanitaire';
    return 'Plomberie';
};
$normalizePriority = static function (string $raw): string {
    $rawLower = mb_strtolower(trim($raw), 'UTF-8');
    if (str_contains($rawLower, 'urgent')) return 'Urgente';
    if (str_contains($rawLower, 'faible')) return 'Faible';
    return 'Moyenne';
};
$normalizeCondition = static function (string $raw): string {
    $rawLower = mb_strtolower(trim($raw), 'UTF-8');
    if (str_contains($rawLower, 'critique') || str_contains($rawLower, 'urgent')) return 'Critique';
    if (str_contains($rawLower, 'suivre') || str_contains($rawLower, 'bon')) return 'À suivre';
    return 'Moyen';
};

foreach ($rMatches[1] as $valuesBlock) {
    preg_match_all("/\(([^)]+)\)/", $valuesBlock, $tupleMatches);
    foreach ($tupleMatches[1] as $tuple) {
        $fields = str_getcsv($tuple, ',', "'");
        $fields = array_map(static fn($v) => trim((string)$v), $fields);
        if (count($fields) < 8) continue;

        $title = mb_substr($fields[1] ?: 'Intervention sans titre', 0, 120, 'UTF-8');
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fields[2]) ? $fields[2] . ' 10:00:00' : date('Y-m-d H:i:s');
        $location = mb_substr($fields[3] ?: 'BEAC Bafoussam', 0, 80, 'UTF-8');
        $category = $normalizeCategory($fields[4] ?? '');
        $part = mb_substr($fields[5] ?? '', 0, 120, 'UTF-8');
        $notes = mb_substr($fields[6] ?? '', 0, 3000, 'UTF-8');
        $condition = $normalizeCondition($fields[7] ?? '');
        $priority = $normalizePriority($fields[8] ?? ($fields[7] ?? ''));

        $status = 'À traiter';
        $obsLower = mb_strtolower($notes, 'UTF-8');
        if (str_contains($obsLower, 'resolu') || str_contains($obsLower, 'résolu') || str_contains($obsLower, 'termin')) {
            $status = 'Résolue';
        }

        $rowsToInsert[] = [
            'title' => $title,
            'location' => $location,
            'category' => $category,
            'priority' => $priority,
            'status' => $status,
            'condition' => $condition,
            'part' => $part,
            'notes' => $notes !== '' ? $notes : 'Rapport migré depuis GMISPF V1.',
            'created_at' => $date,
        ];
    }
}

echo count($rowsToInsert) . " intervention(s) extraite(s) du dump.\n";

if ($dryRun) {
    echo "\n[SIMULATION] Aucune modification appliquée à la base de données (--dry-run).\n";
    exit(0);
}

if (!empty($rowsToInsert)) {
    $db->beginTransaction();
    try {
        $stmtReport = $db->prepare("
            INSERT INTO reports (owner_id, title, location, category, priority, status, condition, part, notes, created_at, updated_at, version, archived)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0)
        ");
        $stmtAudit = $db->prepare("
            INSERT INTO audit (report_id, user_id, action, title, changes, created_at)
            VALUES (?, ?, 'create', ?, ?, ?)
        ");

        $count = 0;
        foreach ($rowsToInsert as $r) {
            $stmtReport->execute([
                $ownerId,
                $r['title'],
                $r['location'],
                $r['category'],
                $r['priority'],
                $r['status'],
                $r['condition'],
                $r['part'],
                $r['notes'],
                $r['created_at'],
                $r['created_at'],
            ]);
            $newId = (int) $db->lastInsertId();
            $auditPayload = json_encode(['import' => 'v1_migration', 'title' => $r['title']], JSON_UNESCAPED_UNICODE);
            $stmtAudit->execute([$newId, $ownerId, $r['title'], $auditPayload, $r['created_at']]);
            $count++;
        }

        $db->commit();
        echo "\nSuccès : {$count} intervention(s) insérée(s) dans la base V2 avec journalisation d’audit.\n";
    } catch (Throwable $e) {
        $db->rollBack();
        fwrite(STDERR, "Erreur durant la transaction de migration : " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Opération terminée.\n";
