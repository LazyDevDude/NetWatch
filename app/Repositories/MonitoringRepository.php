<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\DiscordNotificationService;
use PDO;

final class MonitoringRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function monitoredDevices(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, ip_address
             FROM devices
             WHERE monitoring_enabled = 1
             ORDER BY id'
        );
        return $statement->fetchAll();
    }

    public function settings(): array
    {
        $statement = $this->pdo->query(
            'SELECT ping_interval_seconds, ping_timeout_seconds,
                    latency_warning_ms, latency_critical_ms,
                    packet_loss_warning_percent, packet_loss_critical_percent,
                    offline_after_failures, discord_enabled, discord_webhook_url
             FROM monitoring_settings WHERE id = 1'
        );
        $settings = $statement->fetch();
        return $settings === false ? [] : $settings;
    }

    public function record(array $result, array $settings, string $source): void
    {
        $deviceId = (int) $result['device_id'];
        $isReachable = (bool) $result['is_reachable'];
        $latency = $result['response_time_ms'];
        $packetLoss = $result['packet_loss_percent'];
        $status = 'offline';

        if ($isReachable) {
            $status = ((float) $latency >= (float) $settings['latency_warning_ms']
                || (float) $packetLoss >= (float) $settings['packet_loss_warning_percent'])
                ? 'warning' : 'online';
        }

        $this->pdo->beginTransaction();
        try {
            $deviceStatement = $this->pdo->prepare(
                'SELECT name, current_status FROM devices
                 WHERE id = :device_id AND monitoring_enabled = 1
                 FOR UPDATE'
            );
            $deviceStatement->execute(['device_id' => $deviceId]);
            $device = $deviceStatement->fetch();
            if ($device === false) {
                throw new \InvalidArgumentException('Device is missing or monitoring is disabled.');
            }
            if (!$isReachable && (int) ($settings['offline_after_failures'] ?? 1) > 1) {
                $failureStatement = $this->pdo->prepare(
                    'SELECT is_reachable FROM monitoring_results
                     WHERE device_id = :device_id
                     ORDER BY checked_at DESC LIMIT :limit'
                );
                $failureStatement->bindValue('device_id', $deviceId, PDO::PARAM_INT);
                $failureStatement->bindValue('limit', max(1, (int) $settings['offline_after_failures'] - 1), PDO::PARAM_INT);
                $failureStatement->execute();
                $consecutiveFailures = 1;
                foreach ($failureStatement->fetchAll() as $previousResult) {
                    if ((int) $previousResult['is_reachable'] === 1) {
                        break;
                    }
                    $consecutiveFailures++;
                }
                if ($consecutiveFailures < (int) $settings['offline_after_failures']) {
                    $status = (string) $device['current_status'];
                }
            }

            $resultStatement = $this->pdo->prepare(
                'INSERT INTO monitoring_results
                 (device_id, checked_at, is_reachable, response_time_ms, packet_loss_percent, error_message, source)
                 VALUES (:device_id, :checked_at, :is_reachable, :response_time_ms, :packet_loss_percent, :error_message, :source)'
            );
            $resultStatement->execute([
                'device_id' => $deviceId,
                'checked_at' => $result['checked_at'],
                'is_reachable' => $isReachable ? 1 : 0,
                'response_time_ms' => $latency,
                'packet_loss_percent' => $packetLoss,
                'error_message' => $result['error_message'] ?: null,
                'source' => $source,
            ]);

            $updateDeviceStatement = $this->pdo->prepare(
                'UPDATE devices
                 SET current_status = :status,
                     current_latency_ms = :latency,
                     packet_loss_percent = :packet_loss,
                     last_seen_at = CASE WHEN :is_reachable = 1 THEN :last_seen_at ELSE last_seen_at END,
                     status_changed_at = CASE WHEN current_status <> :new_status THEN :status_changed_at ELSE status_changed_at END
                 WHERE id = :device_id AND monitoring_enabled = 1'
            );
            $updateDeviceStatement->execute([
                'status' => $status,
                'latency' => $latency,
                'packet_loss' => $packetLoss,
                'is_reachable' => $isReachable ? 1 : 0,
                'last_seen_at' => $result['checked_at'],
                'new_status' => $status,
                'status_changed_at' => $result['checked_at'],
                'device_id' => $deviceId,
            ]);
            $this->updateIncidentsAndAlerts(
                $deviceId,
                (string) $device['name'],
                (string) $device['current_status'],
                $status,
                $result,
                $settings
            );
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function updateIncidentsAndAlerts(
        int $deviceId,
        string $deviceName,
        string $previousStatus,
        string $currentStatus,
        array $result,
        array $settings
    ): void {
        $checkedAt = (string) $result['checked_at'];
        $incidentType = match ($currentStatus) {
            'offline' => 'offline',
            'warning' => ((float) ($result['response_time_ms'] ?? 0) >= (float) $settings['latency_warning_ms'])
                ? 'high_latency' : 'packet_loss',
            default => null,
        };

        if ($incidentType !== null) {
            $openIncident = $this->findOpenIncident($deviceId, $incidentType);
            if ($openIncident === null) {
                $otherIncident = $this->findAnyOpenIncident($deviceId);
                if ($otherIncident !== null) {
                    $closeStatement = $this->pdo->prepare(
                        'UPDATE incidents SET ended_at = :checked_at, last_observed_at = :checked_at WHERE id = :id'
                    );
                    $closeStatement->execute(['checked_at' => $checkedAt, 'id' => $otherIncident['id']]);
                }
                $incidentId = $this->createIncident(
                    $deviceId,
                    $incidentType,
                    $checkedAt,
                    $currentStatus === 'offline' ? 'critical' : 'warning',
                    $deviceName,
                    $result
                );
                $this->createAlert(
                    $deviceId,
                    $incidentId,
                    $incidentType === 'offline'
                        ? 'device_offline'
                        : ($incidentType === 'high_latency' ? 'high_latency' : 'packet_loss'),
                    $currentStatus === 'offline' ? 'critical' : 'warning',
                    $deviceName . match ($incidentType) {
                        'offline' => ' is offline',
                        'high_latency' => ' has high latency',
                        default => ' has packet loss',
                    },
                    $this->incidentMessage($incidentType, $result),
                    $deviceId . ':' . $incidentType . ':' . $checkedAt
                );
            } else {
                $statement = $this->pdo->prepare(
                    'UPDATE incidents SET last_observed_at = :last_observed_at WHERE id = :id'
                );
                $statement->execute(['last_observed_at' => $checkedAt, 'id' => $openIncident['id']]);
            }
            return;
        }

        if ($previousStatus === 'offline' || $previousStatus === 'warning') {
            $openIncident = $this->findAnyOpenIncident($deviceId);
            if ($openIncident !== null) {
                $statement = $this->pdo->prepare(
                    'UPDATE incidents SET ended_at = :ended_at, last_observed_at = :last_observed_at WHERE id = :id'
                );
                $statement->execute([
                    'ended_at' => $checkedAt,
                    'last_observed_at' => $checkedAt,
                    'id' => $openIncident['id'],
                ]);
                $this->createAlert(
                    $deviceId,
                    (int) $openIncident['id'],
                    'device_recovery',
                    'info',
                    $deviceName . ' recovered',
                    'The device is reachable again.',
                    $deviceId . ':recovery:' . $checkedAt
                );
            }
        }
    }

    private function findOpenIncident(int $deviceId, string $type): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, started_at FROM incidents
             WHERE device_id = :device_id AND type = :type AND ended_at IS NULL
             ORDER BY started_at DESC LIMIT 1'
        );
        $statement->execute(['device_id' => $deviceId, 'type' => $type]);
        $incident = $statement->fetch();
        return $incident === false ? null : $incident;
    }

    private function findAnyOpenIncident(int $deviceId): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT id FROM incidents
             WHERE device_id = :device_id AND ended_at IS NULL
               AND type IN ('offline', 'high_latency', 'packet_loss')
             ORDER BY started_at DESC LIMIT 1"
        );
        $statement->execute(['device_id' => $deviceId]);
        $incident = $statement->fetch();
        return $incident === false ? null : $incident;
    }

    private function createIncident(
        int $deviceId,
        string $type,
        string $checkedAt,
        string $severity,
        string $deviceName,
        array $result
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO incidents
             (device_id, type, started_at, last_observed_at, severity, summary, details)
             VALUES (:device_id, :type, :started_at, :last_observed_at, :severity, :summary, :details)'
        );
        $statement->execute([
            'device_id' => $deviceId,
            'type' => $type,
            'started_at' => $checkedAt,
            'last_observed_at' => $checkedAt,
            'severity' => $severity,
            'summary' => $deviceName . ' monitoring incident',
            'details' => $this->incidentMessage($type, $result),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    private function createAlert(
        int $deviceId,
        int $incidentId,
        string $type,
        string $severity,
        string $title,
        string $message,
        string $deduplicationKey
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO alerts
             (device_id, incident_id, type, severity, title, message, deduplication_key)
             VALUES (:device_id, :incident_id, :type, :severity, :title, :message, :deduplication_key)'
        );
        $statement->execute([
            'device_id' => $deviceId,
            'incident_id' => $incidentId,
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'deduplication_key' => $deduplicationKey,
        ]);
        $settings = $this->settings();
        try {
            (new DiscordNotificationService())->notify($settings, $title, $message, $severity);
        } catch (\Throwable $exception) {
            error_log('NetWatch Discord notification failed: ' . $exception->getMessage());
        }
    }

    private function incidentMessage(string $type, array $result): string
    {
        if ($type === 'offline') {
            return (string) ($result['error_message'] ?: 'The device did not respond to ICMP ping.');
        }
        if ($type === 'high_latency') {
            return 'Measured latency: ' . (string) $result['response_time_ms'] . ' ms.';
        }
        return 'Measured packet loss: ' . (string) $result['packet_loss_percent'] . '%.';
    }
}
