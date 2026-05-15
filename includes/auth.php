<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

function auth_current_user()
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $conn = tastemap_db();
    $stmt = $conn->prepare('SELECT id, email, nickname, email_verified_at, created_at FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }

    return $user;
}

function auth_require_login()
{
    if (!auth_current_user()) {
        header('Location: login.php');
        exit;
    }
}

function auth_find_user_by_email($email)
{
    $conn = tastemap_db();
    $stmt = $conn->prepare('SELECT id, email, password_hash, nickname, email_verified_at, created_at FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

function auth_register_user($email, $password, $nickname)
{
    $email = trim($email);
    $nickname = trim($nickname);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => '올바른 이메일을 입력하세요.'];
    }

    $nicknameLength = function_exists('mb_strlen') ? mb_strlen($nickname) : strlen($nickname);
    if ($nicknameLength < 2) {
        return ['ok' => false, 'error' => '닉네임은 2자 이상 입력하세요.'];
    }

    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => '비밀번호는 8자 이상 입력하세요.'];
    }

    if (auth_find_user_by_email($email)) {
        return ['ok' => false, 'error' => '이미 가입된 이메일입니다.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $conn = tastemap_db();
    $stmt = $conn->prepare('INSERT INTO users (email, password_hash, nickname) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $email, $hash, $nickname);

    if (!$stmt->execute()) {
        $stmt->close();
        return ['ok' => false, 'error' => '회원가입 중 오류가 발생했습니다.'];
    }

    $userId = $stmt->insert_id;
    $stmt->close();

    return [
        'ok' => true,
        'user' => [
            'id' => $userId,
            'email' => $email,
            'nickname' => $nickname,
            'email_verified_at' => null,
        ],
    ];
}

function auth_attempt_login($email, $password)
{
    $user = auth_find_user_by_email(trim($email));

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    if (empty($user['email_verified_at'])) {
        return [
            'error' => 'email_not_verified',
            'id' => $user['id'],
            'email' => $user['email'],
            'nickname' => $user['nickname'],
        ];
    }

    return [
        'id' => $user['id'],
        'email' => $user['email'],
        'nickname' => $user['nickname'],
        'email_verified_at' => $user['email_verified_at'],
        'created_at' => $user['created_at'],
    ];
}

function auth_login_user($user)
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
}

function auth_logout_user()
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function auth_create_email_verification_token($userId)
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $conn = tastemap_db();

    $stmt = $conn->prepare('DELETE FROM email_verification_tokens WHERE user_id = ? AND consumed_at IS NULL');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('INSERT INTO email_verification_tokens (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))');
    $stmt->bind_param('is', $userId, $tokenHash);
    $stmt->execute();
    $stmt->close();

    return $token;
}

function auth_verification_url($token)
{
    $config = tastemap_config();
    $baseUrl = isset($config['app_base_url']) ? rtrim($config['app_base_url'], '/') : '';

    if (!$baseUrl && !empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $basePath;
    }

    return $baseUrl . '/verify_email.php?token=' . urlencode($token);
}

function auth_send_verification_email($user)
{
    $token = auth_create_email_verification_token((int) $user['id']);
    $url = auth_verification_url($token);
    $html = mailer_verification_html($user['nickname'], $url);

    return mailer_send_resend($user['email'], '우리들의 맛집 지도 이메일 인증', $html);
}

function auth_verify_email_token($token)
{
    if (!$token) {
        return ['ok' => false, 'error' => '인증 토큰이 없습니다.'];
    }

    $tokenHash = hash('sha256', $token);
    $conn = tastemap_db();

    $stmt = $conn->prepare(
        'SELECT id, user_id FROM email_verification_tokens
         WHERE token_hash = ? AND consumed_at IS NULL AND expires_at >= NOW()'
    );
    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return ['ok' => false, 'error' => '유효하지 않거나 만료된 인증 링크입니다.'];
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = ?');
        $stmt->bind_param('i', $row['user_id']);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('UPDATE email_verification_tokens SET consumed_at = NOW() WHERE id = ?');
        $stmt->bind_param('i', $row['id']);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        return ['ok' => false, 'error' => '이메일 인증 처리 중 오류가 발생했습니다.'];
    }

    return ['ok' => true, 'user_id' => (int) $row['user_id']];
}
