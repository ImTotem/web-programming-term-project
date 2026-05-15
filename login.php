<?php
require_once __DIR__ . '/includes/auth.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $user = auth_attempt_login($email, $password);

    if ($user) {
        auth_login_user($user);
        header('Location: index.php');
        exit;
    }

    $error = '이메일 또는 비밀번호가 올바르지 않습니다.';
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

