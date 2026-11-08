<?php

declare(strict_types=1);

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Network topology</h1><p class="text-secondary mb-0">Connections are stored explicitly; the map does not infer links.</p></div></div>
<div class="row g-3">
    <div class="col-lg-8"><div class="nw-panel p-2"><div id="topology-map" style="height: 520px"></div></div></div>
    <div class="col-lg-4">
        <?php if (has_role('admin', 'operator')): ?>
        <form class="nw-panel p-3 mb-3" method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create">
            <h2 class="h6">Create connection</h2>
            <label class="form-label" for="source_device_id">Source</label><select class="form-select mb-2" id="source_device_id" name="source_device_id" required><option value="">Select device</option><?php foreach ($devices as $device): ?><option value="<?= e($device['id']) ?>"><?= e($device['name']) ?> (<?= e($device['ip_address']) ?>)</option><?php endforeach; ?></select>
            <label class="form-label" for="target_device_id">Target</label><select class="form-select mb-2" id="target_device_id" name="target_device_id" required><option value="">Select device</option><?php foreach ($devices as $device): ?><option value="<?= e($device['id']) ?>"><?= e($device['name']) ?> (<?= e($device['ip_address']) ?>)</option><?php endforeach; ?></select>
            <label class="form-label" for="connection_type">Type</label><input class="form-control mb-2" id="connection_type" name="connection_type" value="ethernet" maxlength="50">
            <label class="form-label" for="label">Label</label><input class="form-control mb-3" id="label" name="label" maxlength="120">
            <button class="btn btn-primary w-100" type="submit">Create connection</button>
        </form>
        <?php endif; ?>
        <div class="nw-panel p-3"><h2 class="h6">Connections</h2><?php foreach ($connections as $connection): ?><div class="d-flex justify-content-between border-bottom border-secondary-subtle py-2"><span><?= e($connection['source_device_id']) ?> → <?= e($connection['target_device_id']) ?></span><?php if (has_role('admin', 'operator')): ?><form method="post"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($connection['id']) ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Remove</button></form><?php endif; ?></div><?php endforeach; ?></div>
    </div>
</div>
<script src="https://unpkg.com/cytoscape@3.30.2/dist/cytoscape.min.js"></script>
<script>
const graphDevices = <?= json_encode($devices, JSON_THROW_ON_ERROR) ?>;
const graphConnections = <?= json_encode($connections, JSON_THROW_ON_ERROR) ?>;
cytoscape({
    container: document.getElementById('topology-map'),
    elements: [
        ...graphDevices.map(device => ({data: {id: `d${device.id}`, label: `${device.name}\n${device.ip_address}`, status: device.current_status}})),
        ...graphConnections.map(connection => ({data: {id: `c${connection.id}`, source: `d${connection.source_device_id}`, target: `d${connection.target_device_id}`, label: connection.label || connection.connection_type}}))
    ],
    style: [
        {selector: 'node', style: {'background-color': node => node.data('status') === 'online' ? '#20c997' : node.data('status') === 'offline' ? '#dc3545' : '#ffc107', label: 'data(label)', color: '#e7f2ff', 'text-wrap': 'wrap', 'text-valign': 'bottom', 'text-margin-y': 8, 'font-size': 10}},
        {selector: 'edge', style: {'line-color': '#2684b8', 'target-arrow-color': '#2684b8', 'target-arrow-shape': 'triangle', label: 'data(label)', color: '#9eb5cc', 'font-size': 9}}
    ],
    layout: {name: 'cose', animate: false}
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
