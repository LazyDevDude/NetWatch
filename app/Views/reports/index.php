<?php

declare(strict_types=1);

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Reports</h1><p class="text-secondary mb-0">Availability, latency, packet loss, and incident history.</p></div><a class="btn btn-outline-info" href="?from=<?= e($from) ?>&to=<?= e($to) ?>&format=csv">Export CSV</a></div>
<form class="nw-panel p-3 row g-2 mb-3"><div class="col-md-5"><label class="form-label" for="from">From</label><input class="form-control" id="from" name="from" type="date" value="<?= e($from) ?>"></div><div class="col-md-5"><label class="form-label" for="to">To</label><input class="form-control" id="to" name="to" type="date" value="<?= e($to) ?>"></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">Apply</button></div></form>
<div class="nw-panel table-responsive mb-4"><table class="table table-dark table-hover mb-0"><thead><tr><th>Device</th><th>Checks</th><th>Availability</th><th>Average latency</th><th>Packet loss</th></tr></thead><tbody><?php foreach ($availability as $row): ?><tr><td><?= e($row['name']) ?><small class="d-block text-secondary"><?= e($row['ip_address']) ?></small></td><td><?= e($row['checks']) ?></td><td><?= e($row['checks'] > 0 ? number_format(((int) $row['successful_checks'] / (int) $row['checks']) * 100, 2) : '0.00') ?>%</td><td><?= e($row['average_latency_ms']) ?> ms</td><td><?= e($row['average_packet_loss_percent']) ?>%</td></tr><?php endforeach; ?></tbody></table></div>
<div class="nw-panel table-responsive"><table class="table table-dark table-hover mb-0"><thead><tr><th>Started</th><th>Device</th><th>Type</th><th>Severity</th><th>Ended</th></tr></thead><tbody><?php if ($incidents === []): ?><tr><td colspan="5" class="text-secondary">No incidents in this range.</td></tr><?php endif; ?><?php foreach ($incidents as $incident): ?><tr><td><?= e($incident['started_at']) ?></td><td><?= e($incident['device_name']) ?></td><td><?= e(ucwords(str_replace('_', ' ', $incident['type']))) ?></td><td><?= e(ucfirst($incident['severity'])) ?></td><td><?= e($incident['ended_at'] ?: 'Open') ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
