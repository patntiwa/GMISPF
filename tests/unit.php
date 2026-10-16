<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
$path = ROOT . '/storage/test-' . bin2hex(random_bytes(8)) . '.sqlite';
putenv('APP_DB=' . $path);
register_shutdown_function(static function () use ($path): void { @unlink($path); });
set_exception_handler(static function (Throwable $e): void { fwrite(STDERR, $e->getMessage() . "\n" . $e->getTraceAsString() . "\n"); exit(1); });
$pdo = new PDO('sqlite:' . $path);
$pdo->exec(file_get_contents(ROOT . '/database/schema.sql'));
$checks = 0;
function check(bool $condition, string $label): void {
    global $checks;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    echo 'PASS: ' . $label . "\n";
    $checks++;
}
function rejected(callable $fn, string $label): void {
    try { $fn(); } catch (DomainException) { check(true, $label); return; }
    throw new RuntimeException('FAIL: ' . $label);
}
foreach (['admin','agent','prestataire','prestataire'] as $i=>$role) {
    query('INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)', ['Test user ' . $i, "test$i@example.invalid", password_hash(bin2hex(random_bytes(20)), PASSWORD_DEFAULT, ['cost'=>12]), $role]);
}
[$admin,$agent,$provider,$other] = query('SELECT * FROM users ORDER BY id')->fetchAll();
$valid = ['title'=>'Pompe à vérifier','location'=>'Bâtiment A','category'=>'Forage','priority'=>'Urgente','status'=>'À traiter','condition'=>'Critique','part'=>'Joint','notes'=>'Diagnostic à réaliser.'];
[$data,$errors] = validate_report($valid);
check(!$errors, 'Valid report accepted');
foreach (['title'=>['x',str_repeat('é',121),[],"nul\0byte"], 'category'=>['injection',[]], 'status'=>['deleted'], 'notes'=>[str_repeat('é',3001)], 'priority'=>['urgent']] as $field=>$values) {
    foreach ($values as $v) { [, $errors] = validate_report([...$valid,$field=>$v]); check(isset($errors[$field]), "Reject malformed $field"); }
}
check(e('<script>"&') === '&lt;script&gt;&quot;&amp;', 'HTML output escapes tags, quotes and ampersands');
check(input(['id'=>['1']], 'id') === '', 'Array parameters are not coerced to strings');
check(can_edit($admin, ['owner_id'=>3]) && !can_edit($agent, ['owner_id'=>3]), 'Administrator writes / agent read-only');
check(can_edit($provider, ['owner_id'=>3]) && !can_edit($provider, ['owner_id'=>4]), 'Provider object-level write permission');
check(can_view($agent, ['owner_id'=>3]) && !can_view($provider, ['owner_id'=>4]), 'Provider object-level read permission');
$id = save_report($valid, $provider, null, 0);
$report = query('SELECT * FROM reports WHERE id=?', [$id])->fetch();
check(report_list($provider, ['q'=>'VÉRIFIER','category'=>'','priority'=>'','status'=>''], 1, 10, null)[1] === 1, 'French search handles accented uppercase');
check($report['owner_id'] === $provider['id'], 'Owner assigned on the server');
check(query('SELECT COUNT(*) FROM audit WHERE report_id=?', [$id])->fetchColumn() === 1, 'Creation journaled');
rejected(static fn()=>save_report($valid,$agent,null,0), 'Agent cannot create');
rejected(static fn()=>save_report($valid,$other,$report,1), 'Other provider cannot modify');
$attack = "Robert'); DROP TABLE reports; -- <script>alert(1)</script>";
save_report([...$valid,'title'=>$attack,'status'=>'Résolue'],$provider,$report,1);
$updated = query('SELECT * FROM reports WHERE id=?',[$id])->fetch();
check(report_list($provider, ['q'=>'','category'=>'','priority'=>'','status'=>'','pending'=>'1'], 1, 10, null)[1] === 0, 'Unresolved filter excludes completed reports');
check($updated['title']===$attack && $updated['version']===2, 'SQL and HTML payloads stored as data');
rejected(static fn()=>save_report($valid,$provider,$report,1), 'Stale update rejected');
check(query('SELECT COUNT(*) FROM audit')->fetchColumn()===2, 'Failed update did not append an audit event');
$filters=['q'=>'','category'=>'','priority'=>'','status'=>''];
check(report_list($other,$filters,1,10,null)[1]===0, 'List queries enforce provider scope');
check(report_list($provider,[...$filters,'q'=>"' OR 1=1 --"],1,10,null)[1]===0, 'Search SQL injection cannot widen scope');
check(report_list($provider,[...$filters,'q'=>'INT-0001'],1,10,null)[1]===1, 'Reference search works');
check(overview($other,null)[0]['total']===0, 'Statistics respect ownership');
check(count(activity_list($other,null))===0, 'Audit feed respects ownership');
rejected(static fn()=>archive_report($agent,$updated,2), 'Agent cannot archive');
rejected(static fn()=>archive_report($admin,$updated,1), 'Stale archive rejected');
archive_report($admin,$updated,2);
check(report_list($admin,$filters,1,10,null)[1]===0, 'Archive removes report from active list');
check(query('SELECT COUNT(*) FROM reports WHERE id=?',[$id])->fetchColumn()===1, 'Archive preserves record');
check(count(activity_list($admin,null))===3, 'Archive preserves and appends history');
rejected(static fn()=>save_report($valid,$admin,$updated,3), 'Archived record cannot be edited');
query("CREATE TRIGGER reject_test_audit BEFORE INSERT ON audit BEGIN SELECT RAISE(ABORT, 'test'); END");
try { save_report($valid,$admin,null,0); throw new RuntimeException('Transaction should fail'); } catch (PDOException) {}
check(query('SELECT COUNT(*) FROM reports')->fetchColumn()===1, 'Audit failure rolls back report creation');
query('DROP TRIGGER reject_test_audit');
for ($i=0;$i<5;$i++) { check(login_slot('rate@example.invalid','192.0.2.1'), 'Login slot ' . ($i+1)); }
check(!login_slot('rate@example.invalid','192.0.2.2'), 'Account throttle holds across IP addresses');
query('UPDATE login_limits SET expires_at=?',[time()-1]);
check(login_slot('rate@example.invalid','192.0.2.1'), 'Expired throttle window resets');
for ($i=0;$i<24;$i++) { check(login_slot("different$i@example.invalid",'192.0.2.1'), 'IP rate slot ' . ($i+2)); }
check(!login_slot('last@example.invalid','192.0.2.1'), 'IP throttle holds across accounts');
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ROOT)) as $file) {
    if ($file->getExtension()==='php') { token_get_all(file_get_contents($file->getPathname()),TOKEN_PARSE); }
}
check(true, 'All PHP files parse');
echo "\n$checks assertions passed.\n";
