<?php
require_once __DIR__ . '/includes/notes.php';

auth_require_login();

$config = tastemap_config();
$currentUser = auth_current_user();
$records = notes_list_visits((int) $currentUser['id']);
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>기록 목록 | <?= tastemap_h($config['app_name']) ?></title>
    <link rel="stylesheet" href="<?= tastemap_asset_versioned('assets/css/style.css') ?>">
</head>
<body>
    <header class="topbar">
        <div>
            <p class="eyebrow">Culinary Records</p>
            <h1><a href="index.php">우리들의 맛집 지도</a></h1>
        </div>
        <nav class="nav">
            <a href="index.php#map-panel">지도</a>
            <a href="records.php">기록</a>
            <details class="profile-menu">
                <summary><?= tastemap_h($currentUser['nickname']) ?>님</summary>
                <div class="profile-menu-list">
                    <a href="profile.php">내정보</a>
                    <a href="logout.php">로그아웃</a>
                </div>
            </details>
        </nav>
    </header>

    <main class="records-page">
        <section class="records-hero">
            <p class="eyebrow">Saved Data</p>
            <h2>저장된 미식 기록</h2>
            <p>실제로 DB에 저장된 방문 기록과 사진을 최신순으로 확인합니다.</p>
        </section>

        <section class="records-list" aria-label="저장된 미식 기록 목록">
            <?php if (!$records): ?>
                <article class="empty-record-card">
                    <h3>아직 저장된 기록이 없습니다.</h3>
                    <p>지도에서 장소를 선택하고 미식 기록을 저장하면 여기에 표시됩니다.</p>
                    <a class="button primary" href="index.php#map-panel">지도에서 기록하기</a>
                </article>
            <?php endif; ?>

            <?php foreach ($records as $record): ?>
                <article class="record-card">
                    <?php if (!empty($record['photos'][0])): ?>
                        <img class="record-cover" src="<?= tastemap_h($record['photos'][0]) ?>" alt="">
                    <?php else: ?>
                        <div class="record-cover record-cover-empty">
                            <span>사진 없음</span>
                        </div>
                    <?php endif; ?>
                    <div class="record-body">
                        <div class="record-meta">
                            <span><?= tastemap_h($record['visit_date']) ?></span>
                            <span><?= tastemap_h($record['group_name']) ?></span>
                        </div>
                        <h3><?= tastemap_h($record['place_name']) ?></h3>
                        <p class="record-address"><?= tastemap_h($record['road_address_name'] ?: $record['address_name']) ?></p>
                        <?php if ($record['menu_name']): ?>
                            <p class="record-menu">먹은 메뉴: <?= tastemap_h($record['menu_name']) ?></p>
                        <?php endif; ?>
                        <?php if ($record['note']): ?>
                            <p class="record-note"><?= tastemap_h($record['note']) ?></p>
                        <?php endif; ?>
                        <div class="record-footer">
                            <span><?= $record['rating'] !== null ? tastemap_h((string) $record['rating']) . '점' : '별점 없음' ?></span>
                            <span>사진 <?= count($record['photos']) ?>장</span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
