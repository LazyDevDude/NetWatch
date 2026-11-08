<?php

declare(strict_types=1);

ob_start();
?>
<div class="mb-4"><h1 class="h3"><?= $editing ? 'Edit device' : 'Add device' ?></h1></div>
<form class="nw-panel p-4" method="post">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <div class="row g-3">
        <?php foreach (['name' => 'Name', 'hostname' => 'Hostname', 'ip_address' => 'IP address', 'mac_address' => 'MAC address'] as $field => $label): ?>
            <div class="col-md-6"><label class="form-label" for="<?= e($field) ?>"><?= e($label) ?></label><input class="form-control" id="<?= e($field) ?>" name="<?= e($field) ?>" value="<?= e($device[$field]) ?>" <?= $field === 'name' || $field === 'ip_address' ? 'required' : '' ?>><?php if (isset($errors[$field])): ?><div class="text-danger small mt-1"><?= e($errors[$field]) ?></div><?php endif; ?></div>
        <?php endforeach; ?>
        <div class="col-md-6"><label class="form-label" for="device_type">Type</label><select class="form-select" id="device_type" name="device_type"><?php foreach ($types as $type): ?><option value="<?= e($type) ?>" <?= $device['device_type'] === $type ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $type))) ?></option><?php endforeach; ?></select></div>
        <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="3"><?= e($device['description']) ?></textarea></div>
        <div class="col-12 form-check ms-2"><input class="form-check-input" id="monitoring_enabled" name="monitoring_enabled" type="checkbox" <?= (int) $device['monitoring_enabled'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="monitoring_enabled">Enable monitoring</label></div>
    </div>
    <div class="mt-4"><button class="btn btn-primary" type="submit">Save device</button> <a class="btn btn-outline-light" href="<?= e(web_url('/devices')) ?>">Cancel</a></div>
</form>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
