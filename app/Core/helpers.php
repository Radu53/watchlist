<?php

function app_config(): array
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/../../config/config.php';
    }

    return $config;
}

function base_url(): string
{
    $base = app_config()['app']['base_url'] ?? '';
    return rtrim($base, '/');
}

function url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    return base_url() . ($path === '/' ? '/' : $path);
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_username(): ?string
{
    return $_SESSION['username'] ?? null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . url('/login'));
        exit;
    }
}