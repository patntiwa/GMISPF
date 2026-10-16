<?php
// Development only: do not expose any file outside public/.
declare(strict_types=1);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (is_string($path) && preg_match('#^/assets/[a-zA-Z0-9._-]+\.(css|js|svg|woff2)$#D', $path) && is_file(__DIR__ . $path)) {
    return false;
}
if ($path !== '/' && $path !== '/index.php') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Page introuvable.';
    return true;
}
require __DIR__ . '/index.php';
