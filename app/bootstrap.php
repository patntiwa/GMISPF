<?php
declare(strict_types=1);

const ROOT = __DIR__ . '/..';
date_default_timezone_set('UTC');
umask(0077);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

function env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

set_exception_handler(static function (Throwable $error): void {
    // No credentials, submitted content, SQL or stack traces in HTTP responses/logs.
    $reference = bin2hex(random_bytes(6));
    error_log('GMISPF error ' . $reference . ' [' . get_class($error) . ']');
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Operation failed. Reference: $reference\n");
        exit(1);
    }
    http_response_code(500);
    echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Service indisponible</title><h1>Le service est temporairement indisponible.</h1><p>Réessayez plus tard. Référence : ' . $reference . '</p></html>';
});

function db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $file = env('APP_DB', ROOT . '/storage/app.sqlite');
        if (!is_file($file)) {
            throw new RuntimeException('Initialize database using bin/console.php.');
        }
        $pdoClass = class_exists('Pdo\\Sqlite') ? 'Pdo\\Sqlite' : PDO::class;
        $pdo = new $pdoClass('sqlite:' . $file, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
        // SQLite's built-in lower() only handles ASCII. Keep French search case-insensitive.
        $createFunction = method_exists($pdo, 'createFunction') ? 'createFunction' : 'sqliteCreateFunction';
        $deterministic = defined('Pdo\\Sqlite::DETERMINISTIC') ? constant('Pdo\\Sqlite::DETERMINISTIC') : constant('PDO::SQLITE_DETERMINISTIC');
        $pdo->$createFunction('unicode_lower', static fn (string $value): string => mb_strtolower($value, 'UTF-8'), 1, $deterministic);
    }
    return $pdo;
}

function query(string $sql, array $params = []): PDOStatement
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function input(array $source, string $key, string $default = ''): string
{
    return isset($source[$key]) && is_string($source[$key]) ? trim($source[$key]) : $default;
}

function abort_request(int $status, string $message): never
{
    http_response_code($status);
    $errorMessage = $message;
    require ROOT . '/views/error.php';
    exit;
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function boot_session(): void
{
    $secure = env('APP_ENV', 'production') === 'production' || env('APP_HTTPS') === '1';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_name($secure ? '__Host-gmispf' : 'gmispf_dev');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
    if (isset($_SESSION['user_id']) && (time() - ($_SESSION['last_seen'] ?? 0) > 1800 || time() - ($_SESSION['signed_in'] ?? 0) > 28800)) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_seen'] = time();
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function check_csrf(): void
{
    if (!hash_equals($_SESSION['csrf'], input($_POST, 'csrf'))) {
        abort_request(403, 'Votre formulaire a expiré. Rechargez la page et réessayez.');
    }
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $user = query('SELECT id, name, email, role, auth_version FROM users WHERE id = ? AND active = 1', [$_SESSION['user_id']])->fetch();
    if (!$user || $user['auth_version'] !== ($_SESSION['auth_version'] ?? null)) {
        unset($_SESSION['user_id']);
        return null;
    }
    return $user;
}

function can_edit(array $user, array $report): bool
{
    return $user['role'] === 'admin' || ($user['role'] === 'prestataire' && (int) $report['owner_id'] === (int) $user['id']);
}

function can_view(array $user, array $report): bool
{
    return $user['role'] !== 'prestataire' || (int) $report['owner_id'] === (int) $user['id'];
}

function audit(int $userId, ?int $reportId, string $action): void
{
    query('INSERT INTO audit (user_id, report_id, action) VALUES (?, ?, ?)', [$userId, $reportId, $action]);
}

require __DIR__ . '/reports.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/ui.php';
