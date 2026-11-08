<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class SettingsRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function get(): array
    {
        $settings = $this->pdo->query('SELECT * FROM monitoring_settings WHERE id = 1')->fetch();
        return $settings === false ? [] : $settings;
    }

    public function update(array $values, int $userId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE monitoring_settings SET
                ping_interval_seconds = :ping_interval_seconds,
                ping_timeout_seconds = :ping_timeout_seconds,
                latency_warning_ms = :latency_warning_ms,
                latency_critical_ms = :latency_critical_ms,
                packet_loss_warning_percent = :packet_loss_warning_percent,
                packet_loss_critical_percent = :packet_loss_critical_percent,
                offline_after_failures = :offline_after_failures,
                discord_enabled = :discord_enabled,
                discord_webhook_url = :discord_webhook_url,
                timezone = :timezone,
                updated_by = :updated_by
             WHERE id = 1'
        );
        $statement->execute([
            ...$values,
            'updated_by' => $userId,
        ]);
    }
}
