<?php

declare(strict_types=1);

$user = $user ?? current_user() ?? ['username' => 'User', 'role' => 'viewer'];
$currentRoute = (string) ($_SERVER['NETWATCH_ROUTE'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(app_config('name')) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(asset_url('css/netwatch.css')) ?>">
</head>
<body class="nw-app-shell">
    <header class="nw-topbar">
        <button class="btn nw-menu-toggle d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#nw-sidebar" aria-label="Open navigation">
            <i class="bi bi-list"></i>
        </button>
        <div class="nw-topbar-brand">
            <div class="nw-brand-mark"><i class="bi bi-activity"></i> <span>Net<span class="text-info">Watch</span></span></div>
            <span class="nw-topbar-divider"></span>
            <span class="nw-topbar-tagline">Monitor. Detect. Stay Online.</span>
        </div>
        <div class="nw-topbar-actions">
            <span class="nw-live-status"><i class="bi bi-circle-fill"></i> System online</span>
            <span class="nw-topbar-time d-none d-lg-inline"><?= e(date('M j, Y · H:i')) ?></span>
            <a class="nw-icon-button" href="<?= e(web_url('/alerts')) ?>" aria-label="Alerts"><i class="bi bi-bell"></i></a>
            <div class="nw-user-menu"><span class="nw-avatar"><?= e(strtoupper(substr($user['username'], 0, 1))) ?></span><span class="d-none d-sm-inline"><?= e($user['username']) ?></span></div>
            <a class="nw-signout" href="<?= e(web_url('/logout')) ?>">Sign out</a>
        </div>
    </header>
    <div class="container-fluid">
        <div class="row">
            <aside class="offcanvas-md offcanvas-start col-md-2 nw-sidebar p-0" tabindex="-1" id="nw-sidebar" aria-label="Primary navigation">
                <div class="offcanvas-header d-md-none">
                    <div class="nw-brand-mark"><i class="bi bi-activity"></i> Net<span class="text-info">Watch</span></div>
                    <button class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close navigation"></button>
                </div>
                <div class="nw-sidebar-heading"><span class="nw-sidebar-logo"><i class="bi bi-activity"></i></span><span>Net<span class="text-info">Watch</span></span></div>
                <nav class="nav flex-column gap-1">
                    <a class="nav-link <?= $currentRoute === '/dashboard' ? 'active' : '' ?>" href="<?= e(web_url('/dashboard')) ?>"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
                    <a class="nav-link <?= str_starts_with($currentRoute, '/devices') ? 'active' : '' ?>" href="<?= e(web_url('/devices')) ?>"><i class="bi bi-hdd-network"></i> Devices</a>
                    <a class="nav-link <?= $currentRoute === '/network' ? 'active' : '' ?>" href="<?= e(web_url('/network')) ?>"><i class="bi bi-diagram-3"></i> Network map</a>
                    <a class="nav-link <?= $currentRoute === '/alerts' ? 'active' : '' ?>" href="<?= e(web_url('/alerts')) ?>"><i class="bi bi-exclamation-triangle"></i> Alerts</a>
                    <a class="nav-link <?= $currentRoute === '/reports' ? 'active' : '' ?>" href="<?= e(web_url('/reports')) ?>"><i class="bi bi-bar-chart-line"></i> Reports</a>
                    <?php if (has_role('admin')): ?><a class="nav-link <?= $currentRoute === '/settings' ? 'active' : '' ?>" href="<?= e(web_url('/settings')) ?>"><i class="bi bi-sliders2"></i> Settings</a><?php endif; ?>
                </nav>
                <div class="nw-sidebar-footer"><div class="nw-sidebar-online"><i class="bi bi-wifi"></i><span><strong>System online</strong><small>Monitoring active</small></span></div></div>
            </aside>
            <main class="col-md-10 nw-main-content"><?= $content ?></main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
