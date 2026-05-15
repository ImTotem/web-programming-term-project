<?php
require_once __DIR__ . '/includes/bootstrap.php';

$config = tastemap_config();
$hasKakaoJsKey = tastemap_has_real_key($config['kakao_javascript_key']);
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= tastemap_h($config['app_name']) ?> - 우리만의 맛집 지도</title>
    <link rel="stylesheet" href="<?= tastemap_asset('assets/css/style.css') ?>">
    <?php if ($hasKakaoJsKey): ?>
        <script src="//dapi.kakao.com/v2/maps/sdk.js?appkey=<?= tastemap_h($config['kakao_javascript_key']) ?>&libraries=services"></script>
    <?php endif; ?>
</head>
<body>
    <header class="topbar">
        <div>
            <p class="eyebrow">Couple & Family Restaurant Map</p>
            <h1>TasteMap</h1>
        </div>
        <nav class="nav">
            <a href="#features">기능</a>
            <a href="#schema">DB 설계</a>
            <a href="docs/tastemap-design.md">기획 문서</a>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-copy">
                <p class="eyebrow">카카오맵 기반 PHP/MySQL 텀프로젝트</p>
                <h2>우리 가족, 우리 커플에게 맞는 맛집을 함께 고르는 지도</h2>
                <p>
                    음식점과 카페를 검색하고, 그룹별 방문 기록과 취향 평가를 쌓아
                    다음 외식 장소를 추천하는 TasteMap 프로젝트 시작 화면입니다.
                </p>
                <div class="hero-actions">
                    <a class="button primary" href="#map-panel">장소 검색하기</a>
                    <a class="button secondary" href="db/schema.sql">DB 스키마 보기</a>
                </div>
            </div>
            <div class="score-card" aria-label="추천 점수 예시">
                <span>추천 점수</span>
                <strong>4.62</strong>
                <p>평균 평점, 재방문 의사, 상황 태그를 조합해 계산</p>
            </div>
        </section>

        <?php if (!$hasKakaoJsKey): ?>
            <section class="notice">
                <strong>카카오 JavaScript 키가 아직 없습니다.</strong>
                <span><code>config.example.php</code>를 복사해 <code>config.php</code>를 만들고 API 키를 입력하면 지도가 활성화됩니다.</span>
            </section>
        <?php endif; ?>

        <section id="map-panel" class="workspace">
            <form class="search-panel" id="place-search-form">
                <div>
                    <label for="keyword">장소 검색</label>
                    <input id="keyword" name="keyword" type="search" placeholder="예: 홍대 파스타, 강남 카페">
                </div>
                <div>
                    <label for="category">카테고리</label>
                    <select id="category" name="category">
                        <option value="FD6">음식점</option>
                        <option value="CE7">카페</option>
                    </select>
                </div>
                <button type="submit">검색</button>
            </form>

            <div class="map-layout">
                <div id="map" class="map-box">
                    <p>카카오맵 API 키를 설정하면 이 영역에 지도가 표시됩니다.</p>
                </div>
                <aside class="results-panel">
                    <h3>검색 결과</h3>
                    <ul id="place-results">
                        <li class="empty">검색어를 입력하면 음식점/카페 후보가 표시됩니다.</li>
                    </ul>
                </aside>
            </div>
        </section>

        <section id="features" class="feature-grid">
            <article>
                <h3>그룹 맛집 지도</h3>
                <p>커플, 가족, 친구 모임별로 같은 장소도 다른 상태와 평가를 저장합니다.</p>
            </article>
            <article>
                <h3>취향 궁합 평가</h3>
                <p>맛, 가격, 분위기, 접근성, 재방문 의사를 구성원별로 기록합니다.</p>
            </article>
            <article>
                <h3>상황별 추천</h3>
                <p>데이트, 가족 외식, 가성비, 조용한 곳 같은 상황 태그로 후보를 추천합니다.</p>
            </article>
            <article>
                <h3>방문 기록</h3>
                <p>날짜, 지출, 사진, 메모를 남겨 단순 리뷰가 아닌 추억 지도로 확장합니다.</p>
            </article>
        </section>

        <section id="schema" class="schema-summary">
            <h2>주요 DB 테이블</h2>
            <p>사용자, 그룹, 그룹 멤버, 식당 캐시, 저장 맛집, 리뷰, 방문 기록, 사진, 태그를 관계형으로 설계합니다.</p>
            <div class="table-tags">
                <span>users</span>
                <span>groups</span>
                <span>group_members</span>
                <span>restaurants</span>
                <span>saved_places</span>
                <span>reviews</span>
                <span>visits</span>
                <span>visit_photos</span>
                <span>tags</span>
                <span>restaurant_tags</span>
            </div>
        </section>
    </main>

    <script>
        window.TASTEMAP_HAS_KAKAO = <?= $hasKakaoJsKey ? 'true' : 'false' ?>;
    </script>
    <script src="<?= tastemap_asset('assets/js/app.js') ?>"></script>
</body>
</html>

