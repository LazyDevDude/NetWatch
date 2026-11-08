<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Repositories\DeviceRepository;
use App\Repositories\DashboardRepository;
use App\Repositories\MonitoringRepository;
use App\Repositories\ReportRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\UserRepository;

$pdo = db();
$loginUser = (new UserRepository($pdo))->findByLogin('admin@example.test');
if ($loginUser === null) {
    throw new RuntimeException('User login lookup smoke test failed.');
}
$pdo->exec("DELETE FROM devices WHERE ip_address = '198.51.100.10'");

$devices = new DeviceRepository($pdo);
$page = $devices->search(null, null, null, 1, 25);
if (!isset($page['items'], $page['total'], $page['page'], $page['per_page'])) {
    throw new RuntimeException('Device pagination smoke test failed.');
}
$deviceId = $devices->create([
    'name' => 'Smoke Device',
    'hostname' => 'smoke.local',
    'ip_address' => '198.51.100.10',
    'mac_address' => '',
    'device_type' => 'server',
    'description' => 'Temporary smoke test device',
    'monitoring_enabled' => 1,
], null);

$monitoring = new MonitoringRepository($pdo);
$settings = $monitoring->settings();
$dashboard = new DashboardRepository($pdo);
$dashboardSummary = $dashboard->summary();
$reports = new ReportRepository($pdo);
$reports->deviceAvailability('2026-01-01', '2026-01-01');
$reports->incidents('2026-01-01', '2026-01-01');
$storedSettings = (new SettingsRepository($pdo))->get();
if ($storedSettings === [] || !array_key_exists('total_devices', $dashboardSummary)) {
    throw new RuntimeException('Dashboard or settings repository smoke test failed.');
}
$results = [
    [
        'is_reachable' => false,
        'checked_at' => '2026-01-01 00:00:00',
        'response_time_ms' => null,
        'packet_loss_percent' => 100,
        'error_message' => 'smoke failure',
    ],
    [
        'is_reachable' => false,
        'checked_at' => '2026-01-01 00:00:30',
        'response_time_ms' => null,
        'packet_loss_percent' => 100,
        'error_message' => 'smoke failure',
    ],
    [
        'is_reachable' => true,
        'checked_at' => '2026-01-01 00:01:00',
        'response_time_ms' => 2.5,
        'packet_loss_percent' => 0,
        'error_message' => null,
    ],
];

foreach ($results as $result) {
    $monitoring->record([...$result, 'device_id' => $deviceId], $settings, 'smoke-test');
}

$device = $devices->find($deviceId);
$incidentStatement = $pdo->prepare('SELECT COUNT(*) FROM incidents WHERE device_id = :device_id');
$incidentStatement->execute(['device_id' => $deviceId]);
$alertStatement = $pdo->prepare('SELECT COUNT(*) FROM alerts WHERE device_id = :device_id');
$alertStatement->execute(['device_id' => $deviceId]);

if (($device['current_status'] ?? null) !== 'online'
    || (int) $incidentStatement->fetchColumn() !== 1
    || (int) $alertStatement->fetchColumn() !== 2
) {
    $pdo->exec("DELETE FROM devices WHERE id = " . (int) $deviceId);
    throw new RuntimeException('Monitoring lifecycle smoke test failed.');
}

$pdo->exec("DELETE FROM devices WHERE id = " . (int) $deviceId);
echo "Monitoring lifecycle smoke test passed.\n";
