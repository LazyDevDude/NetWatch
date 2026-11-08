<?php

declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function verify_csrf(?string $token): void
{
    $storedToken = $_SESSION['_csrf'] ?? '';
    if (!is_string($token) || $storedToken === '' || !hash_equals($storedToken, $token)) {
        http_response_code(419);
        exit('Invalid request token.');
    }
}
