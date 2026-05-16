<?php

require_once __DIR__ . '/auth.php';

function notes_save_visit($userId, array $payload, array $files)
{
    $conn = tastemap_db();
    $placeName = trim($payload['place_name'] ?? '');
    $latitude = isset($payload['latitude']) ? (float) $payload['latitude'] : 0.0;
    $longitude = isset($payload['longitude']) ? (float) $payload['longitude'] : 0.0;

    if ($placeName === '' || !$latitude || !$longitude) {
        return ['ok' => false, 'error' => '장소 정보가 부족합니다.'];
    }

    $rating = trim($payload['rating'] ?? '');
    if ($rating !== '') {
        $ratingValue = (float) $rating;
        if ($ratingValue < 0.5 || $ratingValue > 5 || fmod($ratingValue * 10, 5.0) !== 0.0) {
            return ['ok' => false, 'error' => '별점은 0.5점 단위로 입력하세요.'];
        }
    } else {
        $ratingValue = null;
    }

    $conn->begin_transaction();

    try {
        $groupId = notes_ensure_default_group($conn, $userId);
        $restaurantId = notes_upsert_restaurant($conn, $payload, $placeName, $latitude, $longitude);
        notes_upsert_saved_place($conn, $groupId, $restaurantId, $userId);
        $visitId = notes_insert_visit($conn, $groupId, $restaurantId, $userId, $payload, $ratingValue);
        $photoCount = notes_store_visit_photos($conn, $visitId, $files);

        $conn->commit();

        return [
            'ok' => true,
            'visit_id' => $visitId,
            'restaurant_id' => $restaurantId,
            'photo_count' => $photoCount,
        ];
    } catch (InvalidArgumentException $e) {
        $conn->rollback();
        return ['ok' => false, 'error' => $e->getMessage()];
    } catch (Throwable $e) {
        $conn->rollback();
        return ['ok' => false, 'error' => '기록 저장 중 오류가 발생했습니다.'];
    }
}

function notes_ensure_default_group(mysqli $conn, $userId)
{
    $stmt = $conn->prepare(
        'SELECT g.id
         FROM groups g
         INNER JOIN group_members gm ON gm.group_id = g.id
         WHERE gm.user_id = ?
         ORDER BY gm.role = "owner" DESC, g.id ASC
         LIMIT 1'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        return (int) $row['id'];
    }

    $inviteCode = notes_generate_invite_code($conn);
    $groupName = '나의 맛집 지도';
    $type = 'custom';
    $stmt = $conn->prepare('INSERT INTO groups (name, type, invite_code, owner_user_id) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('sssi', $groupName, $type, $inviteCode, $userId);
    $stmt->execute();
    $groupId = $stmt->insert_id;
    $stmt->close();

    $role = 'owner';
    $stmt = $conn->prepare('INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $groupId, $userId, $role);
    $stmt->execute();
    $stmt->close();

    return $groupId;
}

function notes_generate_invite_code(mysqli $conn)
{
    for ($i = 0; $i < 10; $i += 1) {
        $code = strtoupper(bin2hex(random_bytes(4)));
        $stmt = $conn->prepare('SELECT id FROM groups WHERE invite_code = ? LIMIT 1');
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$exists) {
            return $code;
        }
    }

    throw new RuntimeException('초대 코드 생성에 실패했습니다.');
}

function notes_upsert_restaurant(mysqli $conn, array $payload, $placeName, $latitude, $longitude)
{
    $placeId = trim($payload['kakao_place_id'] ?? '');
    if ($placeId === '') {
        $placeId = 'manual_' . substr(hash('sha256', $placeName . $latitude . $longitude), 0, 32);
    }
    $placeId = substr($placeId, 0, 50);

    $categoryName = trim($payload['category_name'] ?? '');
    $addressName = trim($payload['address_name'] ?? '');
    $roadAddressName = trim($payload['road_address_name'] ?? '');
    $phone = trim($payload['phone'] ?? '');
    $placeUrl = trim($payload['place_url'] ?? '');

    $stmt = $conn->prepare(
        'INSERT INTO restaurants
            (kakao_place_id, name, category_name, address_name, road_address_name, phone, place_url, latitude, longitude)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            id = LAST_INSERT_ID(id),
            name = VALUES(name),
            category_name = VALUES(category_name),
            address_name = VALUES(address_name),
            road_address_name = VALUES(road_address_name),
            phone = VALUES(phone),
            place_url = VALUES(place_url),
            latitude = VALUES(latitude),
            longitude = VALUES(longitude)'
    );
    $stmt->bind_param('sssssssdd', $placeId, $placeName, $categoryName, $addressName, $roadAddressName, $phone, $placeUrl, $latitude, $longitude);
    $stmt->execute();
    $restaurantId = $stmt->insert_id;
    $stmt->close();

    return $restaurantId;
}

function notes_upsert_saved_place(mysqli $conn, $groupId, $restaurantId, $userId)
{
    $status = 'visited';
    $stmt = $conn->prepare(
        'INSERT INTO saved_places (group_id, restaurant_id, status, created_by)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE status = VALUES(status)'
    );
    $stmt->bind_param('iisi', $groupId, $restaurantId, $status, $userId);
    $stmt->execute();
    $stmt->close();
}

function notes_insert_visit(mysqli $conn, $groupId, $restaurantId, $userId, array $payload, $ratingValue)
{
    $menuName = trim($payload['menu'] ?? '');
    $note = trim($payload['note'] ?? '');
    $willRevisit = 1;
    $stmt = $conn->prepare(
        'INSERT INTO visits
            (group_id, restaurant_id, visit_date, menu_name, rating, note, will_revisit, created_by)
         VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('iisdsii', $groupId, $restaurantId, $menuName, $ratingValue, $note, $willRevisit, $userId);
    $stmt->execute();
    $visitId = $stmt->insert_id;
    $stmt->close();

    return $visitId;
}

function notes_store_visit_photos(mysqli $conn, $visitId, array $files)
{
    if (empty($files['photos']) || empty($files['photos']['name'])) {
        return 0;
    }

    $uploadRoot = __DIR__ . '/../uploads/visits';
    if (!is_dir($uploadRoot) && !mkdir($uploadRoot, 0775, true)) {
        throw new RuntimeException('업로드 폴더를 만들 수 없습니다.');
    }

    $photoCount = 0;
    $names = is_array($files['photos']['name']) ? $files['photos']['name'] : [$files['photos']['name']];
    $tmpNames = is_array($files['photos']['tmp_name']) ? $files['photos']['tmp_name'] : [$files['photos']['tmp_name']];
    $errors = is_array($files['photos']['error']) ? $files['photos']['error'] : [$files['photos']['error']];
    $sizes = is_array($files['photos']['size']) ? $files['photos']['size'] : [$files['photos']['size']];

    foreach ($names as $index => $name) {
        if ($errors[$index] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($errors[$index] === UPLOAD_ERR_INI_SIZE || $errors[$index] === UPLOAD_ERR_FORM_SIZE || $sizes[$index] > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('사진은 한 장당 5MB 이하로 업로드하세요.');
        }
        if ($errors[$index] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('사진 업로드에 실패했습니다. 다시 선택해 주세요.');
        }

        $extension = notes_image_extension($tmpNames[$index]);
        $fileName = 'visit_' . $visitId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = $uploadRoot . '/' . $fileName;

        if (!move_uploaded_file($tmpNames[$index], $targetPath)) {
            throw new RuntimeException('사진 파일을 저장하지 못했습니다.');
        }

        $relativePath = 'uploads/visits/' . $fileName;
        $sortOrder = $photoCount;
        $stmt = $conn->prepare('INSERT INTO visit_photos (visit_id, file_path, sort_order) VALUES (?, ?, ?)');
        $stmt->bind_param('isi', $visitId, $relativePath, $sortOrder);
        $stmt->execute();
        $stmt->close();
        $photoCount += 1;
    }

    return $photoCount;
}

function notes_image_extension($tmpPath)
{
    $info = @getimagesize($tmpPath);
    $mime = $info['mime'] ?? '';
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($extensions[$mime])) {
        throw new InvalidArgumentException('사진은 JPG, PNG, WebP, GIF 형식만 업로드할 수 있습니다.');
    }

    return $extensions[$mime];
}
