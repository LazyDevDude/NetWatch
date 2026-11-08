<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/Helpers/auth.php';
require_once __DIR__ . '/../app/Helpers/csrf.php';
require_once __DIR__ . '/../app/Helpers/view.php';

$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$webPath = rtrim((string) app_config('web_path'), '/');
$path = '/' . ltrim(substr($requestPath, strlen($webPath)), '/');
$path = rtrim($path, '/') ?: '/';
$_SERVER['NETWATCH_ROUTE'] = $path;

$routes = [
    '/' => static function (): void {
        redirect(is_authenticated() ? '/dashboard' : '/login');
    },
    '/login' => __DIR__ . '/login.php',
    '/logout' => __DIR__ . '/logout.php',
    '/dashboard' => __DIR__ . '/dashboard.php',
    '/devices' => static function (): void {
        $_GET['action'] = 'index';
        require __DIR__ . '/devices.php';
    },
    '/devices/create' => static function (): void {
        $_GET['action'] = 'create';
        require __DIR__ . '/devices.php';
    },
    '/network' => __DIR__ . '/network.php',
    '/alerts' => __DIR__ . '/alerts.php',
    '/reports' => __DIR__ . '/reports.php',
    '/settings' => __DIR__ . '/settings.php',
];

if (isset($routes[$path])) {
    $handler = $routes[$path];
    is_string($handler) ? require $handler : $handler();
    exit;
}

if (preg_match('#^/devices/([1-9][0-9]*)/edit$#', $path, $matches)) {
    $_GET['action'] = 'edit';
    $_GET['id'] = $matches[1];
    require __DIR__ . '/devices.php';
    exit;
}

if (preg_match('#^/devices/([1-9][0-9]*)$#', $path, $matches)) {
    $_GET['action'] = 'show';
    $_GET['id'] = $matches[1];
    require __DIR__ . '/devices.php';
    exit;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
render('errors/404');
