<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

header("Content-Security-Policy: default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; connect-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'; object-src 'none'");
header_remove('X-Powered-By');
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cache-Control: no-store, private');
if (env('APP_ENV', 'production') === 'production') {
    header('Strict-Transport-Security: max-age=31536000');
}
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    abort_request(405, 'Méthode non autorisée.');
}
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) {
    abort_request(413, 'Le formulaire dépasse la taille autorisée.');
}
boot_session();
$demoEnabled = env('APP_DEMO') === '1' && env('APP_ENV', 'production') !== 'production';
// A demonstration never reads users or operational data, even with an old session.
$user = $demoEnabled ? null : current_user();
$page = input($_GET, 'page', 'dashboard');
$demo = $demoEnabled && !$user && ($page !== 'login');
if (!$user && !$demo && $page !== 'login') {
    redirect('/?page=login');
}
if ($demoEnabled) {
    // Only the fictional demo can be embedded by the Arena preview. Authenticated pages cannot.
    header("Content-Security-Policy: default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; connect-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors https://arena.ai https://*.arena.ai; object-src 'none'");
}
if ($demo) {
    $user = ['id' => 0, 'name' => 'Alex Morgan', 'email' => '', 'role' => 'demo'];
}
$demoRows = null;
if ($demo) {
    require ROOT . '/app/demo.php';
    $demoRows = demo_reports();
}
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$errors = [];
$loginError = '';
$report = null;
$allowed = ['login', 'dashboard', 'reports', 'installations', 'history', 'help', 'account', 'new', 'report', 'edit', 'export'];
if (!in_array($page, $allowed, true)) {
    abort_request(404, 'Cette page n’existe pas.');
}
if (in_array($page, ['report', 'edit'], true)) {
    $id = filter_var(input($_GET, 'id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id) {
        if ($demo) {
            foreach ($demoRows as $r) { if ($r['id'] === $id) { $report = $r; break; } }
        } else {
            $report = query('SELECT r.*, u.name AS owner_name FROM reports r JOIN users u ON u.id=r.owner_id WHERE r.id=?', [$id])->fetch() ?: null;
        }
    }
    if (!$report || !can_view($user, $report)) { abort_request(404, 'Intervention introuvable.'); }
}
if ($page === 'new' && !$demo && !in_array($user['role'], ['admin', 'prestataire'], true)) {
    abort_request(403, 'Votre rôle permet uniquement la consultation.');
}
if ($page === 'edit' && !$demo && (!can_edit($user, $report) || $report['archived'])) {
    abort_request(403, 'Vous ne pouvez pas modifier cette intervention.');
}
$data = $report ?? ['title' => '', 'location' => '', 'category' => 'Sanitaire', 'part' => '', 'condition' => 'À suivre', 'priority' => 'Moyenne', 'status' => 'À traiter', 'notes' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if ($demoEnabled) { abort_request(403, 'La démonstration est en lecture seule. Aucune donnée n’a été enregistrée.'); }
    $action = input($_POST, 'action');
    if ($page === 'login' && $action === 'login') {
        $email = strtolower(input($_POST, 'email'));
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        if (strlen($email) > 254 || strlen($password) > 72 || !login_slot($email, $_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
            http_response_code(429);
            header('Retry-After: 900');
            $loginError = 'Connexion impossible. Réessayez dans 15 minutes.';
        } elseif (authenticate($email, $password)) {
            redirect('/');
        } else {
            http_response_code(422);
            $loginError = 'Adresse e-mail ou mot de passe incorrect.';
        }
    } elseif ($user && $action === 'logout') {
        $_SESSION = [];
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/', 'secure' => $cookie['secure'], 'httponly' => true, 'samesite' => 'Lax']);
        session_destroy();
        redirect('/?page=login');
    } elseif (in_array($page, ['new', 'edit'], true) && $action === 'save') {
        [$data, $errors] = validate_report($_POST);
        if (!$errors) {
            try {
                $id = save_report($data, $user, $report, (int) input($_POST, 'version', '0'));
                $_SESSION['flash'] = $report ? 'Les modifications ont été enregistrées.' : 'Votre intervention a été créée.';
                redirect('/?page=report&id=' . $id);
            } catch (DomainException $e) {
                http_response_code(409);
                $errors['global'] = $e->getMessage();
            }
        } else {
            http_response_code(422);
        }
    } elseif ($page === 'report' && $action === 'archive') {
        if ($user['role'] !== 'admin') { abort_request(403, 'Archivage réservé à l’administrateur.'); }
        try { archive_report($user, $report, (int) input($_POST, 'version', '0')); }
        catch (DomainException $e) { abort_request(409, $e->getMessage()); }
        $_SESSION['flash'] = 'Intervention archivée. Elle reste accessible depuis l’historique.';
        redirect('/?page=reports');
    } else { abort_request(400, 'Action non autorisée.'); }
}
if ($page === 'login') {
    if ($user) { redirect('/'); }
    require ROOT . '/views/login.php';
    exit;
}
if (!mb_check_encoding(input($_GET, 'q'), 'UTF-8')) { abort_request(400, 'Recherche non valide.'); }
$filters = ['pending' => input($_GET, 'pending'), 'q' => mb_substr(input($_GET, 'q'), 0, 120), 'category' => input($_GET, 'category'), 'status' => input($_GET, 'status'), 'priority' => input($_GET, 'priority')];
foreach (['category' => CATEGORIES, 'status' => STATUSES, 'priority' => PRIORITIES, 'pending' => ['1']] as $field => $choices) {
    if ($filters[$field] !== '' && !in_array($filters[$field], $choices, true)) { abort_request(400, 'Filtre non valide.'); }
}
$pageNumber = max(1, min(100000, (int) input($_GET, 'p', '1')));
if ($page === 'export') {
    [$exportRows, $exportTotal] = report_list($user, $filters, 1, 5001, $demoRows);
    if ($exportTotal > 5000) { abort_request(422, 'Limitez votre sélection à 5 000 interventions avec les filtres.'); }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="interventions-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Référence', 'Intervention', 'Emplacement', 'Installation', 'Priorité', 'Statut', 'Date'], ';', '"', '');
    foreach ($exportRows as $r) {
        $cells = [report_ref($r['id']), $r['title'], $r['location'], $r['category'], $r['priority'], $r['status'], $r['created_at']];
        $cells = array_map(static fn ($v) => preg_match('/^[\s]*[=+@\-\t\r\n]/u', $v) ? "'" . $v : $v, $cells);
        fputcsv($out, $cells, ';', '"', '');
    }
    fclose($out);
    exit;
}
[$stats, $categories, $months] = overview($user, $demoRows);
$size = $page === 'dashboard' ? 5 : 10;
[$rows, $total] = report_list($user, $filters, $pageNumber, $size, $demoRows);
$activities = activity_list($user, $demoRows, $page === 'history' ? 31 : 5, $page === 'history' ? ($pageNumber - 1) * 30 : 0);
$titles = ['dashboard' => 'Vue d’ensemble', 'reports' => 'Interventions', 'installations' => 'Installations', 'history' => 'Historique', 'help' => 'Centre d’aide', 'account' => 'Mon espace', 'new' => 'Nouvelle intervention', 'report' => 'Détail de l’intervention', 'edit' => 'Modifier l’intervention'];
$title = $titles[$page];
$view = match ($page) { 'dashboard' => 'dashboard', 'reports' => 'reports', 'new', 'edit' => 'form', 'report' => 'detail', default => 'secondary' };
require ROOT . '/views/layout.php';
