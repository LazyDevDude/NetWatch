<?php

declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 · <?= e(app_config('name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset_url('css/netwatch.css')) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="nw-auth-shell">
    <main class="nw-login-card text-center">
        <div class="nw-brand-mark mb-3"><i class="bi bi-activity"></i> Net<span class="text-info">Watch</span></div>
        <h1 class="display-5">404</h1>
        <p class="text-secondary">The requested page does not exist.</p>
        <a class="btn btn-primary" href="<?= e(web_url('/dashboard')) ?>">Return to dashboard</a>
    </main>
</body>
</html>
