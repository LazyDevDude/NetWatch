<?php

declare(strict_types=1);

ob_start();
?>
<h1 class="h3 mb-1">Alerts</h1>
<p class="text-secondary mb-4">Review monitoring alerts and acknowledge handled events.</p>
<div class="nw-panel table-responsive"><table class="table table-dark table-hover align-middle mb-0"><thead><tr><th>Created</th><th>Device</th><th>Alert</th><th>Severity</th><th>Status</th><th></th></tr></thead><tbody>
<?php if ($alerts === []): ?><tr><td class="text-secondary" colspan="6">No alerts recorded.</td></tr><?php endif; ?>
<?php foreach ($alerts as $alert): ?><tr><td><?= e($alert['created_at']) ?></td><td><?= e($alert['device_name'] ?? 'Network') ?></td><td><strong><?= e($alert['title']) ?></strong><small class="d-block text-secondary"><?= e($alert['message']) ?></small></td><td><span class="badge text-bg-<?= $alert['severity'] === 'critical' ? 'danger' : ($alert['severity'] === 'warning' ? 'warning' : 'success') ?>"><?= e(ucfirst($alert['severity'])) ?></span></td><td><?= (int) $alert['is_acknowledged'] === 1 ? 'Acknowledged' : 'Open' ?></td><td><?php if ((int) $alert['is_acknowledged'] === 0 && has_role('admin', 'operator')): ?><form method="post"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= e($alert['id']) ?>"><button class="btn btn-sm btn-outline-info" type="submit">Acknowledge</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
