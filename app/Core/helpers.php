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