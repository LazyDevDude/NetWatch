<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/Helpers/auth.php';
require_once __DIR__ . '/../app/Helpers/view.php';

use App\Middleware\AuthMiddleware;
use App\Repositories\DashboardRepository;

AuthMiddleware::requireAuthentication();
$repository = new DashboardRepository(db());
render('dashboard/index', [
    'user' => current_user(),
    'summary' => $repository->summary(),
    'latency' => $repository->recentResults(),
    'alerts' => $repository->recentAlerts(),
]);
