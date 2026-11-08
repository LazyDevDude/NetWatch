<?php

declare(strict_types=1);

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1"><?= e($device['name']) ?></h1><p class="text-secondary mb-0"><?= e($device['description'] ?? '') ?></p></div><?php if (has_role('admin', 'operator')): ?><a class="btn btn-outline-info" href="<?= e(web_url('/devices/' . $device['id'] . '/edit')) ?>">Edit</a><?php endif; ?></div>
<div class="nw-panel p-4">
    <div class="row g-4">
        <div class="col-md-6"><strong>IP address</strong><div><?= e($device['ip_address']) ?></div></div>
        <div class="col-md-6"><strong>Hostname</strong><div><?= e($device['hostname'] ?: '—') ?></div></div>
        <div class="col-md-6"><strong>Type</strong><div><?= e(ucwords(str_replace('_', ' ', $device['device_type']))) ?></div></div>
        <div class="col-md-6"><strong>MAC address</strong><div><?= e($device['mac_address'] ?: '—') ?></div></div>
        <div class="col-md-6"><strong>Current status</strong><div><?= e(ucfirst($device['current_status'])) ?></div></div>
        <div class="col-md-6"><strong>Last seen</strong><div><?= e($device['last_seen_at'] ?: '—') ?></div></div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
