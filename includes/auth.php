<?php

require_once __DIR__ . '/db.php';

function auth_current_user()
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $conn = tastemap_db();
    $stmt = $conn->prepare('SELECT id, email, nickname, created_at FROM users WHERE id = ?');
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
    $stmt = $conn->prepare('SELECT id, email, password_hash, nickname, created_at FROM users WHERE email = ?');
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
        ],
    ];
}

function auth_attempt_login($email, $password)
{
    $user = auth_find_user_by_email(trim($email));

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    return [
        'id' => $user['id'],
        'email' => $user['email'],
        'nickname' => $user['nickname'],
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
