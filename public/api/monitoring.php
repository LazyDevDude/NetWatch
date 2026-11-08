<?php

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

use App\Repositories\MonitoringRepository;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$configuredKey = getenv('NETWATCH_AGENT_KEY') ?: '';
$providedKey = (string) ($_SERVER['HTTP_X_NETWATCH_AGENT_KEY'] ?? '');
$timestamp = (string) ($_SERVER['HTTP_X_NETWATCH_AGENT_TIMESTAMP'] ?? '');
$signature = (string) ($_SERVER['HTTP_X_NETWATCH_AGENT_SIGNATURE'] ?? '');
$body = (string) file_get_contents('php://input');
$validTimestamp = ctype_digit($timestamp) && abs(time() - (int) $timestamp) <= 300;
$expectedSignature = hash_hmac('sha256', $timestamp . "\n" . $body, $configuredKey);
if ($configuredKey === '' || $providedKey === '' || $timestamp === '' || $signature === ''
    || !$validTimestamp || !hash_equals($configuredKey, $providedKey)
    || !hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized agent.']);
    exit;
}

$repository = new MonitoringRepository(db());
$action = (string) ($_GET['action'] ?? '');

if ($action === 'devices' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'devices' => $repository->monitoredDevices(),
        'settings' => $repository->settings(),
    ]);
    exit;
}

if ($action === 'results' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode($body, true);
    if (!is_array($payload) || !is_array($payload['results'] ?? null)
        || count($payload['results']) > 500) {
        http_response_code(400);
        echo json_encode(['error' => 'Expected a JSON object with a results array.']);
        exit;
    }

    $settings = $repository->settings();
    $accepted = 0;
    foreach ($payload['results'] as $result) {
        if (!is_array($result)
            || filter_var($result['device_id'] ?? null, FILTER_VALIDATE_INT) === false
            || !isset($result['is_reachable'], $result['checked_at'])
            || !is_bool($result['is_reachable'])
            || !is_string($result['checked_at'])
            || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result['checked_at'])
            || (isset($result['response_time_ms']) && (!is_numeric($result['response_time_ms']) || (float) $result['response_time_ms'] < 0))
            || (isset($result['packet_loss_percent']) && (!is_numeric($result['packet_loss_percent']) || (float) $result['packet_loss_percent'] < 0 || (float) $result['packet_loss_percent'] > 100))
        ) {
            http_response_code(422);
            echo json_encode(['error' => 'Each result requires a valid device_id, boolean is_reachable, and checked_at.']);
            exit;
        }
        $repository->record([
            'device_id' => (int) $result['device_id'],
            'is_reachable' => $result['is_reachable'],
            'checked_at' => $result['checked_at'],
            'response_time_ms' => isset($result['response_time_ms']) ? (float) $result['response_time_ms'] : null,
            'packet_loss_percent' => isset($result['packet_loss_percent']) ? (float) $result['packet_loss_percent'] : null,
            'error_message' => isset($result['error_message']) ? (string) $result['error_message'] : null,
        ], $settings, 'python-agent');
        $accepted++;
    }

    echo json_encode(['accepted' => $accepted]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Unknown monitoring action.']);
