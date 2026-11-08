<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/Helpers/auth.php';
require_once __DIR__ . '/../app/Helpers/csrf.php';
require_once __DIR__ . '/../app/Helpers/view.php';

use App\Middleware\AuthMiddleware;
use App\Repositories\AuditRepository;
use App\Repositories\ConnectionRepository;

AuthMiddleware::requireAuthentication();
$repository = new ConnectionRepository(db());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthMiddleware::requireRole('admin', 'operator');
    verify_csrf($_POST['_csrf'] ?? null);
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create') {
        $source = filter_var($_POST['source_device_id'] ?? null, FILTER_VALIDATE_INT);
        $target = filter_var($_POST['target_device_id'] ?? null, FILTER_VALIDATE_INT);
        if ($source === false || $target === false || $source === $target) {
            http_response_code(422);
            exit('Select two distinct devices.');
        }
        try {
            $repository->create($source, $target, trim((string) ($_POST['connection_type'] ?? 'ethernet')), trim((string) ($_POST['label'] ?? '')), (int) current_user()['id']);
            (new AuditRepository(db()))->record((int) current_user()['id'], 'create', 'network_connection', null, ['source_device_id' => $source, 'target_device_id' => $target]);
        } catch (PDOException $exception) {
            if ((int) $exception->errorInfo[1] === 1062) {
                http_response_code(409);
                exit('That connection already exists.');
            }
            throw $exception;
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false) {
            http_response_code(422);
            exit('Invalid connection ID.');
        }
        $repository->delete($id);
        (new AuditRepository(db()))->record((int) current_user()['id'], 'delete', 'network_connection', $id);
    }
    redirect('/network');
}

$graph = $repository->graph();
render('network/index', $graph);
