<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByLogin(string $login): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, username, email, password_hash, role
             FROM users
             WHERE is_active = 1 AND (username = :username OR email = :email)
             LIMIT 1'
        );
        $statement->execute(['username' => $login, 'email' => $login]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function updateLastLogin(int $userId): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $userId]);
    }
}
