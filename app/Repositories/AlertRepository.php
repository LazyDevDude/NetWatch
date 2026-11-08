<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AlertRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        return $this->pdo->query(
            'SELECT a.*, d.name AS device_name
             FROM alerts a LEFT JOIN devices d ON d.id = a.device_id
             ORDER BY a.created_at DESC'
        )->fetchAll();
    }

    public function acknowledge(int $id, int $userId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE alerts SET is_acknowledged = 1, acknowledged_by = :user_id,
             acknowledged_at = NOW()
             WHERE id = :id AND is_acknowledged = 0'
        );
        $statement->execute(['id' => $id, 'user_id' => $userId]);
    }
}
