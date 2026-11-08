<?php

declare(strict_types=1);

ob_start();
?>
<main class="nw-login-card">
    <div class="nw-brand-mark">⌁ <span>Net<span class="text-info">Watch</span></span></div>
    <p class="text-secondary mb-4">Sign in to monitor your network.</p>
    <?php if ($error !== null): ?>
        <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(web_url('/login')) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="mb-3">
            <label class="form-label" for="login">Username or email</label>
            <input class="form-control" id="login" name="login" required autocomplete="username">
        </div>
        <div class="mb-4">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-primary w-100" type="submit">Sign in</button>
    </form>
</main>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/auth.php';
