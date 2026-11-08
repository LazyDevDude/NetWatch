<?php

declare(strict_types=1);

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . '/../Views/' . $view . '.php';
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function web_url(string $path = ''): string
{
    $base = (string) app_config('web_path');
    return $base . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

function asset_url(string $path): string
{
    return (string) app_config('project_path') . '/assets/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . web_url($path));
    exit;
}
