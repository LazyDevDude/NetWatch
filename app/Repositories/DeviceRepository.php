<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DeviceRepository
{
    public const TYPES = [
        'router', 'switch', 'server', 'desktop', 'laptop',
        'printer', 'access_point', 'camera', 'nas', 'other',
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function search(?string $search, ?string $status, ?string $type, int $page = 1, int $perPage = 25): array
    {
        $conditions = [];
        $parameters = [];

        if ($search !== null && $search !== '') {
            $conditions[] = '(name LIKE :search_name OR hostname LIKE :search_hostname OR ip_address LIKE :search_ip)';
            $parameters['search_name'] = '%' . $search . '%';
            $parameters['search_hostname'] = '%' . $search . '%';
            $parameters['search_ip'] = '%' . $search . '%';
        }
        if ($status !== null && in_array($status, ['unknown', 'online', 'offline', 'warning'], true)) {
            $conditions[] = 'current_status = :status';
            $parameters['status'] = $status;
        }
        if ($type !== null && in_array($type, self::TYPES, true)) {
            $conditions[] = 'device_type = :type';
            $parameters['type'] = $type;
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $countStatement = $this->pdo->prepare("SELECT COUNT(*) FROM devices {$where}");
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();
        $parameters['limit'] = $perPage;
        $parameters['offset'] = ($page - 1) * $perPage;
        $statement = $this->pdo->prepare(
            "SELECT id, name, hostname, ip_address, mac_address, device_type,
                    description, monitoring_enabled, current_status,
                    current_latency_ms, packet_loss_percent, last_seen_at,
                    status_changed_at, created_at
             FROM devices {$where}
             ORDER BY name ASC
             LIMIT :limit OFFSET :offset"
        );
        $statement->bindValue('limit', $parameters['limit'], PDO::PARAM_INT);
        $statement->bindValue('offset', $parameters['offset'], PDO::PARAM_INT);
        unset($parameters['limit'], $parameters['offset']);
        foreach ($parameters as $name => $value) {
            $statement->bindValue($name, $value);
        }
        $statement->execute();
        return ['items' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM devices WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $device = $statement->fetch();
        return $device === false ? null : $device;
    }

    public function create(array $device, ?int $createdBy): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO devices
             (name, hostname, ip_address, mac_address, device_type, description, monitoring_enabled, created_by)
             VALUES (:name, :hostname, :ip_address, :mac_address, :device_type, :description, :monitoring_enabled, :created_by)'
        );
        $statement->execute([
            'name' => $device['name'],
            'hostname' => $device['hostname'] ?: null,
            'ip_address' => $device['ip_address'],
            'mac_address' => $device['mac_address'] ?: null,
            'device_type' => $device['device_type'],
            'description' => $device['description'] ?: null,
            'monitoring_enabled' => $device['monitoring_enabled'],
            'created_by' => $createdBy,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $device): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE devices
             SET name = :name, hostname = :hostname, ip_address = :ip_address,
                 mac_address = :mac_address, device_type = :device_type,
                 description = :description, monitoring_enabled = :monitoring_enabled
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'name' => $device['name'],
            'hostname' => $device['hostname'] ?: null,
            'ip_address' => $device['ip_address'],
            'mac_address' => $device['mac_address'] ?: null,
            'device_type' => $device['device_type'],
            'description' => $device['description'] ?: null,
            'monitoring_enabled' => $device['monitoring_enabled'],
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM devices WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
