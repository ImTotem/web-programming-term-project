<?php
require_once __DIR__ . '/includes/auth.php';

$error = '';
$notice = '';
$email = '';

if (isset($_GET['registered'])) {
    $notice = '회원가입이 완료되었습니다. 이메일 인증을 마친 뒤 로그인하세요.';
}

if (isset($_GET['verified'])) {
    $notice = '이메일 인증이 완료되었습니다. 이제 로그인할 수 있습니다.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $user = auth_attempt_login($email, $password);

    if ($user && empty($user['error'])) {
        auth_login_user($user);
        header('Location: index.php');
        exit;
    }

    if ($user && isset($user['error']) && $user['error'] === 'email_not_verified') {
        $error = '이메일 인증이 아직 완료되지 않았습니다. 메일함의 인증 링크를 확인하세요.';
    } else {
        $error = '이메일 또는 비밀번호가 올바르지 않습니다.';
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>로그인 - 우리들의 맛집 지도</title>
    <link rel="stylesheet" href="<?= tastemap_asset('assets/css/style.css') ?>">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card">
            <a class="back-link" href="index.php">우리들의 맛집 지도</a>
            <h1>로그인</h1>
            <p>우리 그룹의 맛집 지도와 평가를 이어서 관리합니다.</p>

            <?php if ($notice): ?>
                <div class="form-notice"><?= tastemap_h($notice) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="form-alert"><?= tastemap_h($error) ?></div>
            <?php endif; ?>

            <form method="post" class="auth-form">
                <label for="email">이메일</label>
                <input id="email" name="email" type="email" value="<?= tastemap_h($email) ?>" required>

                <label for="password">비밀번호</label>
                <input id="password" name="password" type="password" required>

                <button type="submit">로그인</button>
            </form>

            <p class="auth-switch">아직 계정이 없나요? <a href="register.php">회원가입</a></p>
        </section>
    </main>
</body>
</html>
