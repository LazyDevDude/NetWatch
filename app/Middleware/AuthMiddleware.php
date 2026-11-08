<?php

declare(strict_types=1);

namespace App\Middleware;

final class AuthMiddleware
{
    public static function requireAuthentication(): void
    {
        if (!\is_authenticated()) {
            \redirect('/login');
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireAuthentication();
        if (!\has_role(...$roles)) {
            http_response_code(403);
            exit('You do not have permission to access this page.');
        }
    }
}
