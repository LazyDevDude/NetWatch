<?php

declare(strict_types=1);

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">Devices</h1><p class="text-secondary mb-0">Manage monitored network devices.</p></div>
    <?php if (has_role('admin', 'operator')): ?><a class="btn btn-primary" href="<?= e(web_url('/devices/create')) ?>">Add device</a><?php endif; ?>
</div>
<form class="nw-panel p-3 mb-3 row g-2" method="get">
    <div class="col-md-5"><label class="visually-hidden" for="search">Search</label><input class="form-control" id="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Name, hostname, or IP"></div>
    <div class="col-md-3"><select class="form-select" name="status"><option value="">All statuses</option><?php foreach (['unknown', 'online', 'offline', 'warning'] as $status): ?><option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><select class="form-select" name="type"><option value="">All types</option><?php foreach ($types as $type): ?><option value="<?= e($type) ?>" <?= $filters['type'] === $type ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $type))) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-1"><button class="btn btn-outline-info w-100" type="submit">Filter</button></div>
</form>
<div class="nw-panel table-responsive">
    <table class="table table-dark table-hover align-middle mb-0">
        <thead><tr><th>Name</th><th>IP address</th><th>Type</th><th>Status</th><th>Latency</th><th>Last seen</th><th></th></tr></thead>
        <tbody>
        <?php if ($devices['items'] === []): ?><tr><td class="text-secondary" colspan="7">No devices found.</td></tr><?php endif; ?>
        <?php foreach ($devices['items'] as $device): ?>
            <tr>
                <td><a href="<?= e(web_url('/devices/' . $device['id'])) ?>"><?= e($device['name']) ?></a></td>
                <td><?= e($device['ip_address']) ?></td>
                <td><?= e(ucwords(str_replace('_', ' ', $device['device_type']))) ?></td>
                <td><span class="badge text-bg-<?= $device['current_status'] === 'online' ? 'success' : ($device['current_status'] === 'offline' ? 'danger' : 'secondary') ?>"><?= e(ucfirst($device['current_status'])) ?></span></td>
                <td><?= $device['current_latency_ms'] !== null ? e($device['current_latency_ms']) . ' ms' : '—' ?></td>
                <td><?= e($device['last_seen_at'] ?? '—') ?></td>
                <td class="text-end"><?php if (has_role('admin', 'operator')): ?><a class="btn btn-sm btn-outline-light" href="<?= e(web_url('/devices/' . $device['id'] . '/edit')) ?>">Edit</a><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if ($devices['total'] > $devices['per_page']): ?>
<nav class="mt-3" aria-label="Device pages"><ul class="pagination">
    <?php for ($page = 1, $pages = (int) ceil($devices['total'] / $devices['per_page']); $page <= $pages; $page++): ?>
        <li class="page-item <?= $page === $devices['page'] ? 'active' : '' ?>"><a class="page-link" href="?<?= e(http_build_query(['search' => $filters['search'], 'status' => $filters['status'], 'type' => $filters['type'], 'page' => $page])) ?>"><?= e($page) ?></a></li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
