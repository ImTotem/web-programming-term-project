<?php

require_once __DIR__ . '/../includes/notes.php';

header('Content-Type: application/json; charset=utf-8');

$user = auth_current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'GET 요청만 사용할 수 있습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$records = notes_list_visits((int) $user['id']);

echo json_encode([
    'ok' => true,
    'records' => $records,
], JSON_UNESCAPED_UNICODE);
