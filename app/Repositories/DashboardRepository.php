<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DashboardRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function summary(): array
    {
        $statement = $this->pdo->query(
            "SELECT
                COUNT(*) AS total_devices,
                COALESCE(SUM(current_status = 'online'), 0) AS online_devices,
                COALESCE(SUM(current_status = 'offline'), 0) AS offline_devices,
                COALESCE(SUM(current_status = 'warning'), 0) AS warning_devices,
                AVG(current_latency_ms) AS average_latency_ms
             FROM devices
             WHERE monitoring_enabled = 1"
        );
        return $statement->fetch() ?: [
            'total_devices' => 0,
            'online_devices' => 0,
            'offline_devices' => 0,
            'warning_devices' => 0,
            'average_latency_ms' => null,
        ];
    }

    public function recentResults(int $limit = 24): array
    {
        $statement = $this->pdo->prepare(
            'SELECT checked_at, AVG(response_time_ms) AS average_latency_ms,
                    AVG(packet_loss_percent) AS average_packet_loss_percent
             FROM monitoring_results
             WHERE checked_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY checked_at
             ORDER BY checked_at ASC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function recentAlerts(int $limit = 5): array
    {
        $statement = $this->pdo->prepare(
            'SELECT a.id, a.title, a.severity, a.message, a.created_at, d.name AS device_name
             FROM alerts a
             LEFT JOIN devices d ON d.id = a.device_id
             ORDER BY a.created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }
}
