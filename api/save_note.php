<?php

require_once __DIR__ . '/../includes/notes.php';

header('Content-Type: application/json; charset=utf-8');

$user = auth_current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST 요청만 사용할 수 있습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = notes_save_visit((int) $user['id'], $_POST, $_FILES);

if (!$result['ok']) {
    http_response_code(400);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
