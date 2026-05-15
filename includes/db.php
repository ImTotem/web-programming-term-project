<?php

require_once __DIR__ . '/bootstrap.php';

function tastemap_db()
{
    static $conn = null;

    if ($conn instanceof mysqli) {
        return $conn;
    }

    $config = tastemap_config();
    $conn = new mysqli(
        $config['db_host'],
        $config['db_user'],
        $config['db_pass'],
        $config['db_name']
    );

    if ($conn->connect_error) {
        throw new RuntimeException('DB 연결 실패: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

