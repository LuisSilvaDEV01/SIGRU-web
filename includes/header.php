<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$pages = [
    'index'      => ['label' => 'Dashboard',         'icon' => 'ti-layout-dashboard'],
    'usuarios'   => ['label' => 'Usuários',           'icon' => 'ti-users'],
    'cadastro'   => ['label' => 'Cadastrar usuário',  'icon' => 'ti-user-plus'],
    'fila'       => ['label' => 'Fila virtual',       'icon' => 'ti-list-numbers'],
    'cardapio'   => ['label' => 'Cardápio',           'icon' => 'ti-clipboard-list'],
    'estoque'    => ['label' => 'Estoque',            'icon' => 'ti-package'],
    'carteiras'  => ['label' => 'Carteiras',          'icon' => 'ti-wallet'],
    'contas'     => ['label' => 'Contas',             'icon' => 'ti-receipt'],
    'precos'     => ['label' => 'Preço das refeições','icon' => 'ti-currency-dollar'],
    'relatorios' => ['label' => 'Relatórios',         'icon' => 'ti-chart-bar'],
];
$page_title = $pages[$current_page]['label'] ?? 'SIGRU';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGRU — <?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.10.0/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="ti ti-building-community" aria-hidden="true"></i>
            <div>
                <span class="brand-name">SIGRU</span>
                <span class="brand-sub">RU · Unimontes</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($pages as $file => $info): ?>
                <a href="<?= $file ?>.php" class="nav-item <?= $current_page === $file ? 'active' : '' ?>">
                    <i class="ti <?= $info['icon'] ?>" aria-hidden="true"></i>
                    <span><?= $info['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">Sistema de Gestão do RU</div>
    </aside>
    <div class="main-wrapper">
        <header class="topbar">
            <h1 class="topbar-title"><?= htmlspecialchars($page_title) ?></h1>
            <span class="topbar-badge">
                <i class="ti ti-calendar" aria-hidden="true"></i>
                <?= date('d/m/Y') ?>
            </span>
        </header>
        <main class="content">
