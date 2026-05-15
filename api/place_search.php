<?php

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$config = tastemap_config();
$apiKey = $config['kakao_rest_api_key'];

if (!tastemap_has_real_key($apiKey)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Kakao REST API 키가 설정되지 않았습니다. config.php를 만들고 kakao_rest_api_key를 입력하세요.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : 'FD6';
$x = isset($_GET['x']) ? trim($_GET['x']) : '';
$y = isset($_GET['y']) ? trim($_GET['y']) : '';

$params = [
    'size' => 15,
];

if ($query !== '') {
    $endpoint = 'https://dapi.kakao.com/v2/local/search/keyword.json';
    $params['query'] = $query;
    if ($category !== '') {
        $params['category_group_code'] = $category;
    }
} else {
    $endpoint = 'https://dapi.kakao.com/v2/local/search/category.json';
    $params['category_group_code'] = $category !== '' ? $category : 'FD6';
}

if ($x !== '' && $y !== '') {
    $params['x'] = $x;
    $params['y'] = $y;
    $params['radius'] = 20000;
    $params['sort'] = 'distance';
}

$url = $endpoint . '?' . http_build_query($params);
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Authorization: KakaoAK {$apiKey}\r\n",
        'timeout' => 5,
    ],
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'error' => '카카오 장소 검색 API 요청에 실패했습니다.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo $response;

