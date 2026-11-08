<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/Helpers/auth.php';
require_once __DIR__ . '/../app/Helpers/view.php';

use App\Middleware\AuthMiddleware;
use App\Repositories\ReportRepository;

AuthMiddleware::requireAuthentication();
$from = (string) ($_GET['from'] ?? date('Y-m-d', strtotime('-6 days')));
$to = (string) ($_GET['to'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) || $from > $to) {
    http_response_code(422);
    exit('Invalid date range.');
}

$repository = new ReportRepository(db());
$availability = $repository->deviceAvailability($from, $to);
$incidents = $repository->incidents($from, $to);

if (($_GET['format'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="netwatch-report-' . $from . '-to-' . $to . '.csv"');
    $output = fopen('php://output', 'wb');
    fputcsv($output, ['Device', 'IP address', 'Checks', 'Successful checks', 'Availability %', 'Average latency ms', 'Average packet loss %']);
    foreach ($availability as $row) {
        $percentage = (int) $row['checks'] > 0 ? ((int) $row['successful_checks'] / (int) $row['checks']) * 100 : 0;
        fputcsv($output, [$row['name'], $row['ip_address'], $row['checks'], $row['successful_checks'], number_format($percentage, 2), $row['average_latency_ms'], $row['average_packet_loss_percent']]);
    }
    fclose($output);
    exit;
}

render('reports/index', compact('from', 'to', 'availability', 'incidents'));
