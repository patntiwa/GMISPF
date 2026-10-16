<?php
declare(strict_types=1);

const CATEGORIES = ['Sanitaire', 'Plomberie', 'Forage'];
const PRIORITIES = ['Faible', 'Moyenne', 'Urgente'];
const CONDITIONS = ['À suivre', 'Moyen', 'Critique'];
const STATUSES = ['À traiter', 'En cours', 'Résolue'];

function validate_report(array $source): array
{
    $data = [];
    $errors = [];
    foreach (['title' => [3, 120], 'location' => [2, 100], 'part' => [0, 120], 'notes' => [0, 3000]] as $field => [$min, $max]) {
        $value = input($source, $field);
        $data[$field] = $value;
        if (!mb_check_encoding($value, 'UTF-8') || mb_strlen($value) < $min || mb_strlen($value) > $max || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            $errors[$field] = "Saisissez entre $min et $max caractères valides.";
        }
    }
    foreach (['category' => CATEGORIES, 'priority' => PRIORITIES, 'condition' => CONDITIONS, 'status' => STATUSES] as $field => $choices) {
        $data[$field] = input($source, $field);
        if (!in_array($data[$field], $choices, true)) {
            $errors[$field] = 'Sélectionnez une valeur de la liste.';
        }
    }
    return [$data, $errors];
}

function scope_sql(array $user): array
{
    return $user['role'] === 'prestataire' ? ['owner_id = ?', [$user['id']]] : ['1 = 1', []];
}

function report_filter(array $rows, array $filters): array
{
    return array_values(array_filter($rows, static function ($row) use ($filters) {
        foreach (['category', 'status', 'priority'] as $field) {
            if ($filters[$field] !== '' && $row[$field] !== $filters[$field]) {
                return false;
            }
        }
        if (($filters['pending'] ?? '') === '1' && $row['status'] === 'Résolue') { return false; }
        return $filters['q'] === '' || mb_stripos($row['title'] . ' ' . $row['location'] . ' ' . report_ref($row['id']), $filters['q']) !== false;
    }));
}

function save_report(array $data, array $user, ?array $report, int $version): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $values = array_map(static fn ($key) => $data[$key], ['title', 'location', 'category', 'part', 'condition', 'priority', 'status', 'notes']);
        if ($report) {
            if (!can_edit($user, $report)) {
                throw new DomainException('Accès refusé.');
            }
            $id = (int) $report['id'];
            $result = query('UPDATE reports SET title=?, location=?, category=?, part=?, condition=?, priority=?, status=?, notes=?, version=version+1, updated_at=CURRENT_TIMESTAMP WHERE id=? AND version=? AND archived=0', [...$values, $id, $version]);
            if ($result->rowCount() !== 1) {
                throw new DomainException('Cette intervention a été modifiée ou archivée. Rechargez la page avant de réessayer.');
            }
            audit((int) $user['id'], $id, $data['status'] !== $report['status'] ? 'Statut : ' . $data['status'] : 'Intervention modifiée');
        } else {
            if (!in_array($user['role'], ['admin', 'prestataire'], true)) {
                throw new DomainException('Accès refusé.');
            }
            query('INSERT INTO reports (title, location, category, part, condition, priority, status, notes, owner_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [...$values, $user['id']]);
            $id = (int) $pdo->lastInsertId();
            audit((int) $user['id'], $id, 'Intervention créée');
        }
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function archive_report(array $user, array $report, int $version): void
{
    if ($user['role'] !== 'admin') {
        throw new DomainException('Accès refusé.');
    }
    db()->beginTransaction();
    try {
        $result = query('UPDATE reports SET archived=1, version=version+1, updated_at=CURRENT_TIMESTAMP WHERE id=? AND version=? AND archived=0', [$report['id'], $version]);
        if ($result->rowCount() !== 1) {
            throw new DomainException('Intervention déjà modifiée ou archivée.');
        }
        audit((int) $user['id'], (int) $report['id'], 'Intervention archivée');
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}

function filters_sql(array $user, array $filters): array
{
    [$where, $params] = scope_sql($user);
    $where .= ' AND archived = 0';
    foreach (['category', 'priority', 'status'] as $field) {
        if ($filters[$field] !== '') {
            $where .= " AND $field = ?";
            $params[] = $filters[$field];
        }
    }
    if (($filters['pending'] ?? '') === '1') { $where .= " AND status != 'Résolue'"; }
    if ($filters['q'] !== '') {
        $where .= " AND (instr(unicode_lower(title || ' ' || location), unicode_lower(?)) > 0 OR id = ?)";
        $params[] = $filters['q'];
        $params[] = preg_match('/^(?:INT-)?0*(\d+)$/i', $filters['q'], $match) ? (int) $match[1] : -1;
    }
    return [$where, $params];
}

function report_list(array $user, array $filters, int $page, int $size, ?array $demoRows): array
{
    if ($demoRows !== null) {
        $rows = report_filter($demoRows, $filters);
        return [array_slice($rows, ($page - 1) * $size, $size), count($rows)];
    }
    [$where, $params] = filters_sql($user, $filters);
    $total = (int) query("SELECT COUNT(*) FROM reports WHERE $where", $params)->fetchColumn();
    // Only internally generated integers are interpolated; all input is bound.
    $offset = ($page - 1) * $size;
    $rows = query("SELECT * FROM reports WHERE $where ORDER BY created_at DESC, id DESC LIMIT $size OFFSET $offset", $params)->fetchAll();
    return [$rows, $total];
}

function overview(array $user, ?array $demoRows): array
{
    $stats = ['total' => 0, 'open' => 0, 'progress' => 0, 'resolved' => 0, 'urgent' => 0];
    $categories = array_fill_keys(CATEGORIES, ['total' => 0, 'open' => 0]);
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $months[date('Y-m', strtotime("first day of -$i months"))] = ['total' => 0, 'resolved' => 0];
    }
    if ($demoRows !== null) {
        $groups = [];
        foreach ($demoRows as $r) {
            $groups[] = [...$r, 'month' => substr($r['created_at'], 0, 7), 'count' => 1];
        }
    } else {
        [$where, $params] = scope_sql($user);
        $groups = query("SELECT category, status, priority, substr(created_at,1,7) AS month, COUNT(*) AS count FROM reports WHERE $where AND archived=0 GROUP BY category, status, priority, month", $params)->fetchAll();
    }
    foreach ($groups as $r) {
        $n = (int) $r['count'];
        $stats['total'] += $n;
        $stats['open'] += $r['status'] === 'À traiter' ? $n : 0;
        $stats['progress'] += $r['status'] === 'En cours' ? $n : 0;
        $stats['resolved'] += $r['status'] === 'Résolue' ? $n : 0;
        $stats['urgent'] += $r['priority'] === 'Urgente' && $r['status'] !== 'Résolue' ? $n : 0;
        $categories[$r['category']]['total'] += $n;
        $categories[$r['category']]['open'] += $r['status'] !== 'Résolue' ? $n : 0;
        if (isset($months[$r['month']])) {
            $months[$r['month']]['total'] += $n;
            $months[$r['month']]['resolved'] += $r['status'] === 'Résolue' ? $n : 0;
        }
    }
    return [$stats, $categories, $months];
}

function activity_list(array $user, ?array $demoRows, int $limit = 6, int $offset = 0): array
{
    if ($demoRows !== null) {
        return array_map(static fn ($r) => ['report_id' => $r['id'], 'action' => $r['status'] === 'Résolue' ? 'Statut : Résolue' : 'Intervention créée', 'name' => 'Équipe maintenance', 'title' => $r['title'], 'created_at' => $r['updated_at']], array_slice($demoRows, $offset, $limit));
    }
    $where = $user['role'] === 'prestataire' ? 'r.owner_id = ? OR (a.report_id IS NULL AND a.user_id = ?)' : '1=1';
    $params = $user['role'] === 'prestataire' ? [$user['id'], $user['id']] : [];
    return query("SELECT a.*, u.name, r.title FROM audit a JOIN users u ON a.user_id=u.id LEFT JOIN reports r ON a.report_id=r.id WHERE $where ORDER BY a.id DESC LIMIT $limit OFFSET $offset", $params)->fetchAll();
}
