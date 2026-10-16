<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#176b5b"><title><?= e($title) ?> · GMISPF</title>
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/app.css"><script src="/assets/app.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<aside class="sidebar" id="sidebar" aria-label="Navigation principale">
<a class="brand" href="<?= e(url('dashboard')) ?>"><span class="brand-mark"><?= icon('layers') ?></span><span>GMISPF<span class="brand-sub">MAINTENANCE & PATRIMOINE</span></span></a>
<div class="workspace"><?= icon('building') ?><div><strong>Agence de Bafoussam</strong><span>BEAC · Espace maintenance</span></div><span class="workspace-dot"></span></div>
<div class="nav-label">ESPACE DE TRAVAIL</div>
<nav>
<?php foreach (['dashboard' => ['grid', 'Vue d’ensemble'], 'reports' => ['tool', 'Interventions'], 'installations' => ['layers', 'Installations'], 'history' => ['history', 'Historique']] as $key => [$symbol, $label]): $active = $page === $key || ($key === 'reports' && in_array($page, ['new', 'edit', 'report'], true)); ?>
<a class="nav-link <?= $active ? 'active' : '' ?>" href="<?= e(url($key)) ?>" <?= $active ? 'aria-current="page"' : '' ?>><?= icon($symbol) ?><span><?= e($label) ?></span><?php if ($key === 'reports'): ?><span class="nav-count"><?= $stats['open'] + $stats['progress'] ?></span><?php endif ?></a>
<?php endforeach ?>
</nav>
<div class="sidebar-bottom"><div class="sidebar-note"><span class="note-icon"><?= icon('shield') ?></span><strong>Un suivi, en toute clarté.</strong><p>Vos interventions et leur historique, au même endroit.</p><a href="<?= e(url('help')) ?>">Découvrir l’espace <?= icon('arrow') ?></a></div>
<a class="nav-link <?= $page === 'help' ? 'active' : '' ?>" href="<?= e(url('help')) ?>"><?= icon('help') ?><span>Centre d’aide</span></a>
<div class="sidebar-footer"><span class="mini-mark">G.</span><span>GMISPF <span class="version">2.0</span><small>Gestion technique simplifiée</small></span></div></div>
</aside>
<div class="app-shell">
<header class="topbar"><div class="breadcrumb"><button class="icon-button mobile-menu" type="button" data-menu aria-controls="sidebar" aria-expanded="false" aria-label="Ouvrir le menu"><?= icon('menu') ?></button><span>Espace de travail</span><?= icon('chevron') ?><strong><?= e(in_array($page, ['new','edit','report'], true) ? 'Interventions' : $title) ?></strong></div>
<div class="topbar-actions"><form class="global-search" method="get" action="/"><input type="hidden" name="page" value="reports"><?php if ($demo): ?><input type="hidden" name="demo" value="1"><?php endif ?><label class="sr-only" for="global-search">Rechercher une intervention</label><?= icon('search') ?><input id="global-search" name="q" placeholder="Rechercher…" maxlength="120"><kbd>/</kbd></form><a href="<?= e(url('reports', ['priority' => 'Urgente', 'pending' => '1'])) ?>" class="icon-button notification" aria-label="Voir les interventions urgentes"><?= icon('bell') ?><?php if ($stats['urgent']): ?><span></span><?php endif ?></a><span class="header-separator"></span><a class="profile" href="<?= e(url('account')) ?>"><span class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span><span class="profile-text"><strong><?= e($user['name']) ?></strong><small><?= e(role_label($user['role'])) ?></small></span><?= icon('down') ?></a></div>
</header>
<main id="main">
<?php if ($demo): ?><div class="demo-banner"><span><span class="live-dot"></span> Aperçu de la V2 <span class="banner-divider">/</span> <span class="demo-description">Données fictives · lecture seule</span></span><a href="/?page=login">Accéder à mon espace <?= icon('arrow') ?></a></div><?php endif ?>
<?php if ($flash): ?><div class="notice success" role="status"><?= icon('check') ?><?= e($flash) ?></div><?php endif ?>
<?php require ROOT . '/views/' . $view . '.php'; ?>
<footer class="page-footer"><span>© <?= date('Y') ?> GMISPF <span>·</span> Agence de Bafoussam</span><span><?= icon('shield') ?> <?= $demo ? 'Environnement de démonstration' : 'Espace authentifié' ?></span></footer>
</main>
</div>
</body></html>
