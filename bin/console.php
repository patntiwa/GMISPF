<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../app/bootstrap.php';

function fail(string $message): never { fwrite(STDERR, $message . "\n"); exit(1); }
function read_password(): string
{
    // Read from stdin, never a process argument, environment variable or persisted config.
    $terminal = function_exists('stream_isatty') && stream_isatty(STDIN);
    if ($terminal) {
        if (PHP_OS_FAMILY === 'Windows' || !function_exists('shell_exec') || !function_exists('exec')) {
            fail('Use a protected stdin pipe; hidden terminal input is unavailable.');
        }
        $state = shell_exec('stty -g 2>/dev/null');
        if (!is_string($state) || trim($state) === '') { fail('Cannot disable terminal echo. Use a protected stdin pipe.'); }
        exec('stty -echo', $unused, $code);
        if ($code !== 0) { fail('Cannot disable terminal echo.'); }
        fwrite(STDERR, 'Password (12+ characters, max 72 bytes): ');
        try { $password = rtrim((string) fgets(STDIN), "\r\n"); }
        finally { exec('stty ' . escapeshellarg(trim($state))); fwrite(STDERR, "\n"); }
    } else { $password = rtrim((string) fgets(STDIN), "\r\n"); }
    if (!mb_check_encoding($password, 'UTF-8') || mb_strlen($password) < 12 || strlen($password) > 72) {
        fail('Password must contain at least 12 characters and at most 72 bytes.');
    }
    return $password;
}

$command = $argv[1] ?? 'help';
if ($command === 'init') {
    $file = env('APP_DB', ROOT . '/storage/app.sqlite');
    if (!is_dir(dirname($file))) { fail('Create the database directory first (permissions 0700).'); }
    if (is_file($file)) { fail('Database already exists. No changes made.'); }
    $pdo = new PDO('sqlite:' . $file, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec(file_get_contents(ROOT . '/database/schema.sql'));
    chmod($file, 0600);
    echo "Database initialized. Create an administrator with user:create.\n";
} elseif ($command === 'user:create') {
    $email = strtolower(trim($argv[2] ?? ''));
    $name = trim($argv[3] ?? '');
    $role = $argv[4] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email)>254 || mb_strlen($name)<2 || mb_strlen($name)>80 || !in_array($role, ['admin','agent','prestataire'], true)) {
        fail('Usage: php bin/console.php user:create EMAIL "Full name" admin|agent|prestataire');
    }
    if (query('SELECT id FROM users WHERE email=?', [$email])->fetchColumn()) { fail('Account already exists.'); }
    $password = read_password();
    query('INSERT INTO users (name,email,role,password_hash) VALUES (?,?,?,?)', [$name,$email,$role,password_hash($password,PASSWORD_DEFAULT,['cost'=>12])]);
    $id = (int)db()->lastInsertId();
    audit($id, null, 'Compte créé par administration CLI');
    echo "Account created.\n";
} elseif ($command === 'user:list') {
    $rows = query('SELECT id, name, email, role, active, created_at FROM users ORDER BY id ASC')->fetchAll();
    if (!$rows) { echo "No users found.
"; exit(0); }
    printf("%-5s | %-20s | %-30s | %-12s | %-8s | %s
", 'ID', 'Name', 'Email', 'Role', 'Active', 'Created');
    echo str_repeat('-', 95) . "
";
    foreach ($rows as $r) {
        printf("%-5d | %-20s | %-30s | %-12s | %-8s | %s
",
            $r['id'],
            mb_substr($r['name'], 0, 20),
            mb_substr($r['email'], 0, 30),
            $r['role'],
            $r['active'] ? 'yes' : 'no',
            $r['created_at']
        );
    }
} elseif (in_array($command, ['user:password','user:disable'], true)) {
    $user = query('SELECT id FROM users WHERE email=?', [strtolower(trim($argv[2] ?? ''))])->fetch();
    if (!$user) { fail('Account not found.'); }
    // Do not hold a database lock while waiting for interactive input.
    $newHash = $command === 'user:password' ? password_hash(read_password(), PASSWORD_DEFAULT, ['cost'=>12]) : null;
    db()->beginTransaction();
    try {
        if ($command === 'user:password') {
            query('UPDATE users SET password_hash=?, auth_version=auth_version+1 WHERE id=?', [$newHash,$user['id']]);
            audit((int)$user['id'], null, 'Mot de passe renouvelé par administration CLI');
        } else {
            query('UPDATE users SET active=0, auth_version=auth_version+1 WHERE id=?', [$user['id']]);
            audit((int)$user['id'], null, 'Compte désactivé par administration CLI');
        }
        db()->commit();
    } catch (Throwable $e) { db()->rollBack(); throw $e; }
    echo "Account updated. Existing sessions invalidated.\n";
} else {
    echo "GMISPF 2 — PHP 8.3+ / PDO SQLite / mbstring\n\n";
    echo "  init                                      Initialize an empty database\n";
    echo "  user:list                                 List registered users\n";
    echo "  user:create EMAIL \"Full name\" ROLE        Provision a user (password on stdin)\n";
    echo "  user:password EMAIL                       Reset password; invalidate sessions\n";
    echo "  user:disable EMAIL                        Disable account; invalidate sessions\n";
}
