<?php
require_once __DIR__ . '/includes/auth.php';

$error = '';
$notice = '';
$email = '';
$nickname = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $passwordConfirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

    if ($password !== $passwordConfirm) {
        $error = '비밀번호 확인이 일치하지 않습니다.';
    } else {
        $registered = auth_register_user($email, $password, $nickname);

        if ($registered['ok']) {
            $mail = auth_send_verification_email($registered['user']);

            if ($mail['ok']) {
                header('Location: login.php?registered=1');
                exit;
            }

            $notice = '계정은 생성되었지만 인증 메일 발송에 실패했습니다. Resend API 설정을 확인한 뒤 다시 시도하세요.';
            $error = $mail['error'];
        } else {
            $error = $registered['error'];
        }
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>회원가입 - 우리들의 맛집 지도</title>
    <link rel="stylesheet" href="<?= tastemap_asset_versioned('assets/css/style.css') ?>">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card">
            <a class="back-link" href="index.php">우리들의 맛집 지도</a>
            <h1>회원가입</h1>
            <p>우리 그룹의 맛집 기록과 취향 평가를 시작합니다.</p>

            <?php if ($notice): ?>
                <div class="form-notice"><?= tastemap_h($notice) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="form-alert"><?= tastemap_h($error) ?></div>
            <?php endif; ?>

            <form method="post" class="auth-form">
                <label for="email">이메일</label>
                <input id="email" name="email" type="email" value="<?= tastemap_h($email) ?>" required>

                <label for="nickname">닉네임</label>
                <input id="nickname" name="nickname" type="text" value="<?= tastemap_h($nickname) ?>" minlength="2" required>

                <label for="password">비밀번호</label>
                <input id="password" name="password" type="password" minlength="8" required>

                <label for="password_confirm">비밀번호 확인</label>
                <input id="password_confirm" name="password_confirm" type="password" minlength="8" required>

                <button type="submit">회원가입</button>
            </form>

            <p class="auth-switch">이미 계정이 있나요? <a href="login.php">로그인</a></p>
        </section>
    </main>
</body>
</html>
