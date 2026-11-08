<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/Helpers/auth.php';
require_once __DIR__ . '/../app/Helpers/csrf.php';
require_once __DIR__ . '/../app/Helpers/view.php';

use App\Middleware\AuthMiddleware;
use App\Repositories\AuditRepository;
use App\Repositories\SettingsRepository;

AuthMiddleware::requireRole('admin');
$repository = new SettingsRepository(db());
$settings = $repository->get();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['_csrf'] ?? null);
    $values = [
        'ping_interval_seconds' => filter_var($_POST['ping_interval_seconds'] ?? null, FILTER_VALIDATE_INT),
        'ping_timeout_seconds' => filter_var($_POST['ping_timeout_seconds'] ?? null, FILTER_VALIDATE_INT),
        'latency_warning_ms' => filter_var($_POST['latency_warning_ms'] ?? null, FILTER_VALIDATE_FLOAT),
        'latency_critical_ms' => filter_var($_POST['latency_critical_ms'] ?? null, FILTER_VALIDATE_FLOAT),
        'packet_loss_warning_percent' => filter_var($_POST['packet_loss_warning_percent'] ?? null, FILTER_VALIDATE_FLOAT),
        'packet_loss_critical_percent' => filter_var($_POST['packet_loss_critical_percent'] ?? null, FILTER_VALIDATE_FLOAT),
        'offline_after_failures' => filter_var($_POST['offline_after_failures'] ?? null, FILTER_VALIDATE_INT),
        'discord_enabled' => isset($_POST['discord_enabled']) ? 1 : 0,
        'discord_webhook_url' => trim((string) ($_POST['discord_webhook_url'] ?? '')),
        'timezone' => trim((string) ($_POST['timezone'] ?? 'UTC')),
    ];
    foreach ([
        'ping_interval_seconds', 'ping_timeout_seconds', 'latency_warning_ms',
        'latency_critical_ms', 'packet_loss_warning_percent',
        'packet_loss_critical_percent', 'offline_after_failures',
    ] as $field) {
        if ($values[$field] === false || $values[$field] === null || (float) $values[$field] < 0) {
            $errors[$field] = 'Enter a non-negative value.';
        }
    }
    if (!in_array($values['timezone'], timezone_identifiers_list(), true)) {
        $errors['timezone'] = 'Select a valid timezone identifier.';
    }
    if ($values['discord_enabled'] === 1
        && !filter_var($values['discord_webhook_url'], FILTER_VALIDATE_URL)) {
        $errors['discord_webhook_url'] = 'A valid Discord webhook URL is required when notifications are enabled.';
    }
    if ((float) $values['latency_critical_ms'] < (float) $values['latency_warning_ms']
        || (float) $values['packet_loss_critical_percent'] < (float) $values['packet_loss_warning_percent']) {
        $errors['thresholds'] = 'Critical thresholds must be greater than or equal to warning thresholds.';
    }
    if ($errors === []) {
        $repository->update($values, (int) current_user()['id']);
        (new AuditRepository(db()))->record((int) current_user()['id'], 'update', 'monitoring_settings', 1);
        redirect('/settings?saved=1');
    }
    $settings = [...$settings, ...$values];
}

render('settings/index', ['settings' => $settings, 'errors' => $errors, 'saved' => isset($_GET['saved'])]);
