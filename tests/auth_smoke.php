<?php

require_once __DIR__ . '/../includes/auth.php';

function assert_true($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

function delete_test_user($email)
{
    $conn = tastemap_db();
    $stmt = $conn->prepare('DELETE FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->close();
}

$email = 'auth-smoke-' . date('YmdHis') . '@example.test';
$password = 'Passw0rd!';
$nickname = 'Auth Smoke';

delete_test_user($email);

$registered = auth_register_user($email, $password, $nickname);
assert_true($registered['ok'] === true, '회원가입이 성공해야 합니다.');
assert_true((int) $registered['user']['id'] > 0, '회원가입 결과에 사용자 ID가 있어야 합니다.');

$duplicate = auth_register_user($email, $password, $nickname);
assert_true($duplicate['ok'] === false, '같은 이메일은 중복 가입되지 않아야 합니다.');

$login = auth_attempt_login($email, $password);
assert_true($login !== null, '올바른 비밀번호로 로그인되어야 합니다.');
assert_true($login['email'] === $email, '로그인 사용자 이메일이 일치해야 합니다.');

$failedLogin = auth_attempt_login($email, 'wrong-password');
assert_true($failedLogin === null, '잘못된 비밀번호는 로그인되지 않아야 합니다.');

delete_test_user($email);

echo "auth smoke ok\n";
