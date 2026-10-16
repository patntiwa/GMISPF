<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../app/bootstrap.php';
$file = env('APP_DB');
$password = env('TEST_PASSWORD');
if (env('APP_ENV') !== 'test' || !str_starts_with(basename($file), 'test-') || is_file($file) || strlen($password)<20) {
    fwrite(STDERR,"Refusing to initialize anything other than a fresh test database.\n"); exit(1);
}
$pdo = new PDO('sqlite:'.$file);
$pdo->exec(file_get_contents(ROOT.'/database/schema.sql'));
foreach (['admin','agent','prestataire','prestataire'] as $i=>$role) {
    query('INSERT INTO users(name,email,role,password_hash) VALUES(?,?,?,?)', ['Test user '.$i,"user$i@example.invalid",$role,password_hash($password,PASSWORD_DEFAULT,['cost'=>12])]);
}
