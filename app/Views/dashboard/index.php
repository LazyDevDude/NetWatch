<?php

declare(strict_types=1);

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">Live overview of your network status.</p>
    </div>
    <div class="text-end"><span class="nw-live-status"><i class="bi bi-circle-fill"></i> Monitoring active</span><small class="d-block text-secondary mt-1">Updated <?= e(date('H:i:s')) ?></small></div>
</div>
<div class="row g-3">
    <div class="col-sm-6 col-xl-3"><div class="nw-stat-card"><span>Total devices</span><strong id="total-devices"><?= e($summary['total_devices']) ?></strong><small class="text-info"><i class="bi bi-arrow-up-right"></i> Registered devices</small></div></div>
    <div class="col-sm-6 col-xl-3"><div class="nw-stat-card"><span>Online</span><strong class="text-success" id="online-devices"><?= e($summary['online_devices']) ?></strong><small class="text-success"><i class="bi bi-check-circle"></i> <?= $summary['total_devices'] > 0 ? e(number_format(((int) $summary['online_devices'] / (int) $summary['total_devices']) * 100, 1)) . '%' : '0%' ?> availability</small></div></div>
    <div class="col-sm-6 col-xl-3"><div class="nw-stat-card"><span>Offline</span><strong class="text-danger" id="offline-devices"><?= e($summary['offline_devices']) ?></strong><small class="text-danger"><i class="bi bi-x-circle"></i> Requires attention</small></div></div>
    <div class="col-sm-6 col-xl-3"><div class="nw-stat-card"><span>Average latency</span><strong id="average-latency"><?= $summary['average_latency_ms'] !== null ? e(number_format((float) $summary['average_latency_ms'], 1)) . ' ms' : '—' ?></strong><small class="text-info"><i class="bi bi-activity"></i> From monitoring history</small></div></div>
</div>
<div class="row g-3 mt-1">
    <div class="col-lg-8"><div class="nw-panel p-3"><h2 class="h6">Latency history</h2><canvas id="latency-chart" height="100"></canvas></div></div>
    <div class="col-lg-4"><div class="nw-panel p-3 h-100"><div class="d-flex justify-content-between"><h2 class="h6">Device status</h2><a class="small text-info" href="<?= e(web_url('/devices')) ?>">View all</a></div><div class="nw-status-ring"><strong><?= $summary['total_devices'] > 0 ? e(number_format(((int) $summary['online_devices'] / (int) $summary['total_devices']) * 100, 1)) : '0' ?>%</strong><span>Online</span></div><div class="nw-status-legend"><span><i class="nw-dot online"></i> Online <b><?= e($summary['online_devices']) ?></b></span><span><i class="nw-dot offline"></i> Offline <b><?= e($summary['offline_devices']) ?></b></span><span><i class="nw-dot warning"></i> Warning <b><?= e($summary['warning_devices']) ?></b></span></div></div></div>
</div>
<div class="row g-3 mt-1">
    <div class="col-lg-8"><div class="nw-panel p-3"><div class="d-flex justify-content-between"><h2 class="h6">Recent alerts</h2><a class="small text-info" href="<?= e(web_url('/alerts')) ?>">View all</a></div><?php if ($alerts === []): ?><p class="text-secondary mb-0">No alerts recorded.</p><?php else: ?><div class="list-group list-group-flush"><?php foreach ($alerts as $alert): ?><div class="list-group-item bg-transparent text-light border-secondary-subtle"><span class="badge text-bg-<?= $alert['severity'] === 'critical' ? 'danger' : 'warning' ?> me-2"><?= e(ucfirst($alert['severity'])) ?></span><?= e($alert['title']) ?><small class="d-block text-secondary"><?= e($alert['device_name'] ?? 'Network') ?> · <?= e($alert['created_at']) ?></small></div><?php endforeach; ?></div><?php endif; ?></div></div>
    <div class="col-lg-4"><div class="nw-panel p-3 h-100"><h2 class="h6">Warning devices</h2><strong class="display-6 text-warning"><?= e($summary['warning_devices']) ?></strong><p class="text-secondary mb-0">Require attention based on current thresholds.</p><?php if (has_role('admin')): ?><a class="btn btn-sm btn-outline-info mt-3" href="<?= e(web_url('/settings')) ?>">Review thresholds</a><?php else: ?><a class="btn btn-sm btn-outline-info mt-3" href="<?= e(web_url('/devices?status=warning')) ?>">View devices</a><?php endif; ?></div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(() => {
    const endpoint = <?= json_encode(web_url('/api/dashboard.php'), JSON_THROW_ON_ERROR) ?>;
    const latency = <?= json_encode($latency, JSON_THROW_ON_ERROR) ?>;
    const chart = new Chart(document.getElementById('latency-chart'), {
        type: 'line',
        data: {
            labels: latency.map(item => item.checked_at),
            datasets: [{label: 'Average latency (ms)', data: latency.map(item => Number(item.average_latency_ms)), borderColor: '#20c997', backgroundColor: 'rgba(32, 201, 151, .15)', fill: true, tension: .25}]
        },
        options: {responsive: true, plugins: {legend: {labels: {color: '#e7f2ff'}}}, scales: {x: {ticks: {color: '#9eb5cc'}}, y: {beginAtZero: true, ticks: {color: '#9eb5cc'}}}}
    });
    const refresh = async () => {
        try {
            const response = await fetch(endpoint, {headers: {'Accept': 'application/json'}});
            if (!response.ok) return;
            const data = await response.json();
            const summary = data.summary;
            document.getElementById('total-devices').textContent = summary.total_devices;
            document.getElementById('online-devices').textContent = summary.online_devices;
            document.getElementById('offline-devices').textContent = summary.offline_devices;
            document.getElementById('average-latency').textContent = summary.average_latency_ms === null ? '—' : `${Number(summary.average_latency_ms).toFixed(1)} ms`;
            if (Array.isArray(data.latency)) {
                chart.data.labels = data.latency.map(item => item.checked_at);
                chart.data.datasets[0].data = data.latency.map(item => Number(item.average_latency_ms));
                chart.update('none');
            }
        } catch (error) {
            console.error('Dashboard refresh failed', error);
        }
    };
    window.setInterval(refresh, 30000);
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
