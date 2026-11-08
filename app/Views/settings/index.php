<?php

declare(strict_types=1);

ob_start();
?>
<h1 class="h3 mb-1">Monitoring settings</h1>
<p class="text-secondary mb-4">Configure agent polling, alert thresholds, and notifications.</p>
<?php if ($saved): ?><div class="alert alert-success">Settings saved.</div><?php endif; ?>
<?php if (isset($errors['thresholds'])): ?><div class="alert alert-danger"><?= e($errors['thresholds']) ?></div><?php endif; ?>
<form class="nw-panel p-4" method="post">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <div class="row g-3">
        <?php foreach ([
            'ping_interval_seconds' => 'Ping interval (seconds)',
            'ping_timeout_seconds' => 'Ping timeout (seconds)',
            'latency_warning_ms' => 'Latency warning (ms)',
            'latency_critical_ms' => 'Latency critical (ms)',
            'packet_loss_warning_percent' => 'Packet loss warning (%)',
            'packet_loss_critical_percent' => 'Packet loss critical (%)',
            'offline_after_failures' => 'Offline after failures',
        ] as $field => $label): ?>
        <div class="col-md-6"><label class="form-label" for="<?= e($field) ?>"><?= e($label) ?></label><input class="form-control" id="<?= e($field) ?>" name="<?= e($field) ?>" type="number" min="0" step="<?= str_contains($field, 'percent') || str_contains($field, 'latency') ? '0.001' : '1' ?>" value="<?= e($settings[$field]) ?>" required><?php if (isset($errors[$field])): ?><div class="small text-danger"><?= e($errors[$field]) ?></div><?php endif; ?></div>
        <?php endforeach; ?>
        <div class="col-md-6"><label class="form-label" for="timezone">Timezone</label><input class="form-control" id="timezone" name="timezone" value="<?= e($settings['timezone']) ?>" required><?php if (isset($errors['timezone'])): ?><div class="small text-danger"><?= e($errors['timezone']) ?></div><?php endif; ?></div>
        <div class="col-12 form-check ms-2"><input class="form-check-input" id="discord_enabled" name="discord_enabled" type="checkbox" <?= (int) $settings['discord_enabled'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="discord_enabled">Enable Discord notifications</label></div>
        <div class="col-12"><label class="form-label" for="discord_webhook_url">Discord webhook URL</label><input class="form-control" id="discord_webhook_url" name="discord_webhook_url" type="url" value="<?= e($settings['discord_webhook_url'] ?? '') ?>"><?php if (isset($errors['discord_webhook_url'])): ?><div class="small text-danger"><?= e($errors['discord_webhook_url']) ?></div><?php endif; ?></div>
    </div>
    <button class="btn btn-primary mt-4" type="submit">Save settings</button>
</form>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
