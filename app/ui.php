<?php
declare(strict_types=1);
function icon(string $name, string $class = ''): string
{
    $paths = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'tool' => '<path d="M14.5 6.5a5 5 0 0 0-6.2 6.2L3 18a2.1 2.1 0 0 0 3 3l5.3-5.3a5 5 0 0 0 6.2-6.2L14 13l-3-3z"/>',
        'layers' => '<path d="m12 3 10 5-10 5L2 8zm-10 9 10 5 10-5M2 16l10 5 10-5"/>',
        'history' => '<path d="M3 11a9 9 0 1 1 2.6 7M3 4v7h7m2-4v5l3 2"/>',
        'shield' => '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6z"/><path d="m8 12 3 3 5-6"/>',
        'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 4 2c-1.5 1-1.5 1-1.5 3m0 3h.01"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'down' => '<path d="m6 9 6 6 6-6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'download' => '<path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'alert' => '<path d="m12 3 10 18H2zM12 9v5m0 3h.01"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 11h18m-12 4h2m4 0h2"/>',
        'pin' => '<path d="M19 10c0 6-7 11-7 11S5 16 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2"/>',
        'drop' => '<path d="M12 2S5 10 5 15a7 7 0 0 0 14 0c0-5-7-13-7-13Z"/><path d="M8 15a4 4 0 0 0 4 4"/>',
        'pipe' => '<path d="M3 4h7v6h10v7H10a7 7 0 0 1-7-7zm-2 0h11m8 4v11"/>',
        'pump' => '<path d="M5 21V10h10v11M3 21h14M8 10V5h5l4-3M4 5h12m3 9h2v6M15 14h4"/>',
        'logout' => '<path d="M9 4H4v16h5m5-13 5 5-5 5m-6-5h11"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
        'file' => '<path d="M14 3H5v18h14V8zm0 0v5h5M8 12h8m-8 4h6"/>',
        'building' => '<path d="M4 21V7l8-4 8 4v14M2 21h20M8 8v2m8-2v2m-8 3v2m8-2v2m-5 6v-4h2v4"/>',
    ];
    return '<svg class="icon ' . e($class) . '" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['file']) . '</svg>';
}
function report_ref(int|string $id): string { return 'INT-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT); }
function date_fr(string $value, bool $time = false): string { return date($time ? 'd/m/Y à H:i' : 'd/m/Y', strtotime($value)); }
function category_icon(string $category): string { return match ($category) { 'Sanitaire' => 'drop', 'Plomberie' => 'pipe', default => 'pump' }; }
function badge(string $value): string {
    $class = match ($value) { 'Urgente', 'Critique' => 'red', 'En cours', 'Moyenne', 'Moyen' => 'amber', 'Résolue' => 'green', 'À traiter' => 'blue', default => 'gray' };
    return '<span class="badge ' . $class . '"><span class="dot"></span>' . e($value) . '</span>';
}
function url(string $page, array $params = []): string {
    global $demo;
    return '/?' . http_build_query(['page' => $page, ...($demo ? ['demo' => '1'] : []), ...$params]);
}
function role_label(string $role): string { return ['admin' => 'Administrateur', 'agent' => 'Agent · consultation', 'prestataire' => 'Prestataire', 'demo' => 'Espace de démonstration'][$role] ?? $role; }
