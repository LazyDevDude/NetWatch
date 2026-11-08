<?php

declare(strict_types=1);

return [
    'name' => getenv('NETWATCH_APP_NAME') ?: 'NetWatch',
    'web_path' => rtrim((string) (getenv('NETWATCH_WEB_PATH') ?: '/Netwatch/public'), '/'),
    'project_path' => rtrim((string) (getenv('NETWATCH_PROJECT_PATH') ?: '/Netwatch'), '/'),
    'session_name' => getenv('NETWATCH_SESSION_NAME') ?: 'netwatch_session',
    'timezone' => getenv('NETWATCH_TIMEZONE') ?: 'UTC',
    'environment' => getenv('NETWATCH_ENV') ?: 'production',
];
