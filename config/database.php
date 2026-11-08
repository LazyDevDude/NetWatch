<?php

declare(strict_types=1);

return [
    'dsn' => getenv('NETWATCH_DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=netwatch;charset=utf8mb4',
    'username' => getenv('NETWATCH_DB_USER') ?: 'root',
    'password' => getenv('NETWATCH_DB_PASSWORD') ?: '',
];
