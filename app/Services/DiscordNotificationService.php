<?php

declare(strict_types=1);

namespace App\Services;

final class DiscordNotificationService
{
    public function notify(array $settings, string $title, string $message, string $severity): void
    {
        if ((int) ($settings['discord_enabled'] ?? 0) !== 1 || empty($settings['discord_webhook_url'])) {
            return;
        }

        $payload = json_encode([
            'username' => 'NetWatch',
            'embeds' => [[
                'title' => $title,
                'description' => $message,
                'color' => match ($severity) {
                    'critical' => 15158332,
                    'warning' => 16776960,
                    default => 3066993,
                },
            ]],
        ], JSON_THROW_ON_ERROR);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents($settings['discord_webhook_url'], false, $context);
        $status = $http_response_header[0] ?? '';
        if ($response === false || !str_contains($status, ' 2')) {
            throw new \RuntimeException('Discord webhook request failed: ' . ($status ?: 'no response'));
        }
    }
}
