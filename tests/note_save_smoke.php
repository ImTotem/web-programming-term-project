<?php

require_once __DIR__ . '/../includes/notes.php';

function assert_true($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

function delete_note_test_user($email)
{
    $conn = tastemap_db();
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return;
    }

    $userId = (int) $row['id'];
    $stmt = $conn->prepare('DELETE FROM groups WHERE owner_user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}

$email = 'note-smoke-' . date('YmdHis') . '@example.test';
$password = 'Passw0rd!';
$nickname = 'Note Smoke';

delete_note_test_user($email);

$registered = auth_register_user($email, $password, $nickname);
assert_true($registered['ok'] === true, '테스트 사용자가 생성되어야 합니다.');
$userId = (int) $registered['user']['id'];

$payload = [
    'kakao_place_id' => 'note-smoke-place-' . $userId,
    'place_name' => '노트 저장 테스트 식당',
    'category_name' => '음식점 > 테스트',
    'address_name' => '서울 테스트구',
    'road_address_name' => '서울 테스트로 1',
    'phone' => '02-000-0000',
    'place_url' => 'https://place.map.kakao.com/test',
    'latitude' => '37.566826',
    'longitude' => '126.978657',
    'menu' => '테스트 메뉴',
    'note' => '미식 기록 저장 테스트',
    'rating' => '4.5',
];

$result = notes_save_visit($userId, $payload, []);
assert_true($result['ok'] === true, '미식 기록 저장이 성공해야 합니다.');
assert_true((int) $result['visit_id'] > 0, '방문 기록 ID가 있어야 합니다.');

$conn = tastemap_db();
$stmt = $conn->prepare(
    'SELECT v.menu_name, v.rating, v.note, r.name
     FROM visits v
     INNER JOIN restaurants r ON r.id = v.restaurant_id
     WHERE v.id = ?'
);
$stmt->bind_param('i', $result['visit_id']);
$stmt->execute();
$visit = $stmt->get_result()->fetch_assoc();
$stmt->close();

assert_true($visit['name'] === '노트 저장 테스트 식당', '식당 이름이 저장되어야 합니다.');
assert_true($visit['menu_name'] === '테스트 메뉴', '대표 메뉴가 저장되어야 합니다.');
assert_true((string) $visit['rating'] === '4.5', '0.5 단위 별점이 저장되어야 합니다.');
assert_true($visit['note'] === '미식 기록 저장 테스트', '미식 기록이 저장되어야 합니다.');

delete_note_test_user($email);

echo "note save smoke ok\n";
