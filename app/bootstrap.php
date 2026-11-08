<?php

declare(strict_types=1);

if (defined('NETWATCH_BOOTSTRAPPED')) {
    return;
}
define('NETWATCH_BOOTSTRAPPED', true);

$appConfig = require __DIR__ . '/../config/app.php';
$databaseConfig = require __DIR__ . '/../config/database.php';

date_default_timezone_set($appConfig['timezone']);

session_name($appConfig['session_name']);
session_set_cookie_params([
    'httponly' => true,
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'samesite' => 'Lax',
]);
session_start();

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\Controllers\\' => __DIR__ . '/Controllers/',
        'App\\Middleware\\' => __DIR__ . '/Middleware/',
        'App\\Models\\' => __DIR__ . '/Models/',
        'App\\Repositories\\' => __DIR__ . '/Repositories/',
        'App\\Services\\' => __DIR__ . '/Services/',
    ];

    foreach ($prefixes as $prefix => $directory) {
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $directory . str_replace('\\', '/', $relativeClass) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

function db(): PDO
{
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    global $databaseConfig;
    $pdo = new PDO(
        $databaseConfig['dsn'],
        $databaseConfig['username'],
        $databaseConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $pdo;
}

function app_config(string $key): mixed
{
    global $appConfig;
    return $appConfig[$key] ?? null;
}
