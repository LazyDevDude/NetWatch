<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/Helpers/auth.php';
require_once __DIR__ . '/../app/Helpers/csrf.php';
require_once __DIR__ . '/../app/Helpers/view.php';

use App\Middleware\AuthMiddleware;
use App\Repositories\AlertRepository;
use App\Repositories\AuditRepository;

AuthMiddleware::requireAuthentication();
$repository = new AlertRepository(db());
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthMiddleware::requireRole('admin', 'operator');
    verify_csrf($_POST['_csrf'] ?? null);
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        http_response_code(422);
        exit('Invalid alert ID.');
    }
    $repository->acknowledge($id, (int) current_user()['id']);
    (new AuditRepository(db()))->record((int) current_user()['id'], 'acknowledge', 'alert', $id);
    redirect('/alerts');
}
render('alerts/index', ['alerts' => $repository->all()]);
