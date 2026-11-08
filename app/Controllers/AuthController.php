<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;

final class AuthController
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function showLogin(?string $error = null): void
    {
        \render('auth/login', ['error' => $error]);
    }

    public function login(string $login, string $password): bool
    {
        $user = $this->users->findByLogin(trim($login));
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        \login_user($user);
        $this->users->updateLastLogin((int) $user['id']);
        return true;
    }
}
