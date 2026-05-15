<?php
require_once __DIR__ . '/includes/auth.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$result = auth_verify_email_token($token);
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>이메일 인증 - 우리들의 맛집 지도</title>
    <link rel="stylesheet" href="<?= tastemap_asset_versioned('assets/css/style.css') ?>">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card">
            <a class="back-link" href="index.php">우리들의 맛집 지도</a>
            <h1>이메일 인증</h1>

            <?php if ($result['ok']): ?>
                <div class="form-notice">이메일 인증이 완료되었습니다.</div>
                <p class="auth-switch"><a href="login.php?verified=1">로그인하러 가기</a></p>
            <?php else: ?>
                <div class="form-alert"><?= tastemap_h($result['error']) ?></div>
                <p class="auth-switch"><a href="register.php">회원가입으로 돌아가기</a></p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
