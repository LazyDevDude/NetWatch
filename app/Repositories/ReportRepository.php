<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ReportRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function deviceAvailability(string $from, string $to): array
    {
        $statement = $this->pdo->prepare(
            "SELECT d.name, d.ip_address,
                    COUNT(m.id) AS checks,
                    COALESCE(SUM(m.is_reachable), 0) AS successful_checks,
                    ROUND(COALESCE(AVG(m.response_time_ms), 0), 2) AS average_latency_ms,
                    ROUND(COALESCE(AVG(m.packet_loss_percent), 0), 2) AS average_packet_loss_percent
             FROM devices d
             LEFT JOIN monitoring_results m
                ON m.device_id = d.id AND m.checked_at >= :availability_from AND m.checked_at < DATE_ADD(:availability_to, INTERVAL 1 DAY)
             GROUP BY d.id, d.name, d.ip_address
             ORDER BY d.name"
        );
        $statement->execute(['availability_from' => $from, 'availability_to' => $to]);
        return $statement->fetchAll();
    }

    public function incidents(string $from, string $to): array
    {
        $statement = $this->pdo->prepare(
            'SELECT i.started_at, i.ended_at, i.type, i.severity, i.summary, d.name AS device_name
             FROM incidents i JOIN devices d ON d.id = i.device_id
             WHERE i.started_at >= :incident_from AND i.started_at < DATE_ADD(:incident_to, INTERVAL 1 DAY)
             ORDER BY i.started_at DESC'
        );
        $statement->execute(['incident_from' => $from, 'incident_to' => $to]);
        return $statement->fetchAll();
    }
}
