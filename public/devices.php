<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/Helpers/auth.php';
require_once __DIR__ . '/../app/Helpers/csrf.php';
require_once __DIR__ . '/../app/Helpers/view.php';

use App\Controllers\DeviceController;
use App\Middleware\AuthMiddleware;
use App\Repositories\AuditRepository;
use App\Repositories\DeviceRepository;

AuthMiddleware::requireAuthentication();
$controller = new DeviceController(new DeviceRepository(db()), new AuditRepository(db()));
$method = $_SERVER['REQUEST_METHOD'];
$action = (string) ($_GET['action'] ?? 'index');

if ($action === 'delete' && $method === 'POST') {
    AuthMiddleware::requireRole('admin', 'operator');
    verify_csrf($_POST['_csrf'] ?? null);
    $controllerId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($controllerId === false || $controllerId < 1) {
        http_response_code(400);
        exit('Invalid device ID.');
    }
    (new DeviceRepository(db()))->delete($controllerId);
    (new AuditRepository(db()))->record((int) current_user()['id'], 'delete', 'device', $controllerId);
    redirect('/devices');
}

if ($action === 'create' || $action === 'edit') {
    AuthMiddleware::requireRole('admin', 'operator');
    $id = $action === 'edit' ? filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) : null;
    $device = $id ? (new DeviceRepository(db()))->find($id) : null;
    if ($action === 'edit' && $device === null) {
        http_response_code(404);
        exit('Device not found.');
    }

    if ($method === 'POST') {
        verify_csrf($_POST['_csrf'] ?? null);
        $result = $controller->save($_POST, $id ?: null, (int) current_user()['id']);
        if ($result === null) {
            redirect('/devices');
        }
        $controller->showForm($result['device'], $result['errors']);
        exit;
    }
    $controller->showForm($device);
    exit;
}

if ($action === 'show') {
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    $device = $id ? (new DeviceRepository(db()))->find($id) : null;
    if ($device === null) {
        http_response_code(404);
        exit('Device not found.');
    }
    render('devices/show', ['device' => $device]);
    exit;
}

$controller->index();
