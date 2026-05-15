<?php
require_once __DIR__ . '/includes/auth.php';

auth_require_login();

$config = tastemap_config();
$currentUser = auth_current_user();
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>내정보 - <?= tastemap_h($config['app_name']) ?></title>
    <link rel="stylesheet" href="<?= tastemap_asset_versioned('assets/css/style.css') ?>">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card profile-card">
            <a class="back-link" href="index.php">우리들의 맛집 지도</a>
            <p class="eyebrow">My Profile</p>
            <h1>내정보</h1>
            <p>계정 정보와 이메일 인증 상태를 확인합니다.</p>

            <dl class="profile-list">
                <div>
                    <dt>닉네임</dt>
                    <dd><?= tastemap_h($currentUser['nickname']) ?></dd>
                </div>
                <div>
                    <dt>이메일</dt>
                    <dd><?= tastemap_h($currentUser['email']) ?></dd>
                </div>
                <div>
                    <dt>인증 상태</dt>
                    <dd><?= empty($currentUser['email_verified_at']) ? '미인증' : '인증 완료' ?></dd>
                </div>
                <div>
                    <dt>가입일</dt>
                    <dd><?= tastemap_h(substr($currentUser['created_at'], 0, 10)) ?></dd>
                </div>
            </dl>

            <div class="profile-actions">
                <a class="button secondary" href="index.php">지도로 돌아가기</a>
                <a class="button primary" href="logout.php">로그아웃</a>
            </div>
        </section>
    </main>
</body>
</html>
