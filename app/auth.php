<?php
declare(strict_types=1);

/** Atomically consume a slot before password verification. Shared across sessions. */
function login_slot(string $email, string $ip): bool
{
    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        query('DELETE FROM login_limits WHERE expires_at <= ?', [time()]);
        $keys = [hash('sha256', 'email:' . $email) => 5, hash('sha256', 'ip:' . $ip) => 25];
        foreach ($keys as $key => $limit) {
            $attempts = query('SELECT attempts FROM login_limits WHERE key_hash = ?', [$key])->fetchColumn();
            if ($attempts !== false && (int) $attempts >= $limit) {
                $pdo->exec('COMMIT');
                return false;
            }
        }
        foreach ($keys as $key => $limit) {
            query('INSERT INTO login_limits (key_hash, attempts, expires_at) VALUES (?, 1, ?) ON CONFLICT(key_hash) DO UPDATE SET attempts = attempts + 1', [$key, time() + 900]);
        }
        $pdo->exec('COMMIT');
        return true;
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
}

function authenticate(string $email, string $password): bool
{
    $user = query('SELECT * FROM users WHERE email = ?', [$email])->fetch();
    // A valid dummy hash avoids skipping password verification for unknown accounts.
    $dummy = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    $verified = password_verify($password, $user['password_hash'] ?? $dummy);
    if (!$user || !$verified || !$user['active']) {
        return false;
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT, ['cost' => 12])) {
        query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]), $user['id']]);
    }
    session_regenerate_id(true);
    $_SESSION = ['user_id' => $user['id'], 'auth_version' => $user['auth_version'], 'signed_in' => time(), 'last_seen' => time(), 'csrf' => bin2hex(random_bytes(32))];
    audit((int) $user['id'], null, 'Connexion');
    return true;
}
