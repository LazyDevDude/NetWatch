<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ConnectionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function graph(): array
    {
        $devices = $this->pdo->query(
            'SELECT id, name, ip_address, device_type, current_status FROM devices ORDER BY name'
        )->fetchAll();
        $connections = $this->pdo->query(
            'SELECT id, source_device_id, target_device_id, connection_type, label
             FROM network_connections ORDER BY id'
        )->fetchAll();
        return ['devices' => $devices, 'connections' => $connections];
    }

    public function create(int $source, int $target, string $type, ?string $label, int $createdBy): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO network_connections
             (source_device_id, target_device_id, connection_type, label, created_by)
             VALUES (:source, :target, :type, :label, :created_by)'
        );
        $statement->execute([
            'source' => $source,
            'target' => $target,
            'type' => $type,
            'label' => $label ?: null,
            'created_by' => $createdBy,
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM network_connections WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
