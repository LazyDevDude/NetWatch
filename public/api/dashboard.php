<?php

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';
require __DIR__ . '/../../app/Helpers/auth.php';

use App\Repositories\DashboardRepository;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

$repository = new DashboardRepository(db());
echo json_encode([
    'summary' => $repository->summary(),
    'latency' => $repository->recentResults(),
    'alerts' => $repository->recentAlerts(),
]);
