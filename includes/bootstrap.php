<?php

session_start();

function tastemap_config()
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $example = __DIR__ . '/../config.example.php';
    $local = __DIR__ . '/../config.php';

    $config = file_exists($local) ? require $local : require $example;

    return $config;
}

function tastemap_asset($path)
{
    return htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
}

function tastemap_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function tastemap_has_real_key($key)
{
    return $key && strpos($key, 'YOUR_') !== 0;
}

