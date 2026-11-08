<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AuditRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function record(?int $userId, string $action, string $entityType, ?int $entityId, array $details = []): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, details)
             VALUES (:user_id, :action, :entity_type, :entity_id, :ip_address, :details)'
        );
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => filter_var($_SERVER['REMOTE_ADDR'] ?? null, FILTER_VALIDATE_IP) ?: null,
            'details' => $details === [] ? null : json_encode($details, JSON_THROW_ON_ERROR),
        ]);
    }
}
