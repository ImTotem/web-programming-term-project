<?php
require_once __DIR__ . '/includes/auth.php';

$config = tastemap_config();
$hasKakaoJsKey = tastemap_has_real_key($config['kakao_javascript_key']);
$currentUser = auth_current_user();
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= tastemap_h($config['app_name']) ?></title>
    <link rel="stylesheet" href="<?= tastemap_asset_versioned('assets/css/style.css') ?>">
    <?php if ($hasKakaoJsKey): ?>
        <script src="//dapi.kakao.com/v2/maps/sdk.js?appkey=<?= tastemap_h($config['kakao_javascript_key']) ?>&libraries=services"></script>
    <?php endif; ?>
</head>
<body class="<?= $currentUser ? 'app-mode' : 'landing-mode' ?>">
    <header class="topbar">
        <div>
            <p class="eyebrow">Custom Group Restaurant Map</p>
            <h1>우리들의 맛집 지도</h1>
        </div>
        <nav class="nav">
            <?php if ($currentUser): ?>
                <a href="#map-panel">지도</a>
                <details class="profile-menu">
                    <summary><?= tastemap_h($currentUser['nickname']) ?>님</summary>
                    <div class="profile-menu-list">
                        <a href="profile.php">내정보</a>
                        <a href="logout.php">로그아웃</a>
                    </div>
                </details>
            <?php else: ?>
                <a href="#features">기능</a>
                <a href="#schema">DB 설계</a>
                <a href="docs/tastemap-design.md">기획 문서</a>
                <a href="login.php">로그인</a>
                <a class="nav-cta" href="register.php">회원가입</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if (!$currentUser): ?>
    <main>
        <section class="hero">
            <div class="hero-copy">
                <p class="eyebrow">카카오맵 기반 PHP/MySQL 텀프로젝트</p>
                <h2>우리에게 맞는 맛집을 함께 기록하고 고르는 지도</h2>
                <p>
                    원하는 그룹을 만들고, 장소 검색과 방문 기록, 취향 평가를 쌓아
                    다음 외식 후보를 더 쉽게 고릅니다.
                </p>
                <div class="hero-actions">
                    <a class="button primary" href="register.php">시작하기</a>
                    <a class="button secondary" href="login.php">로그인</a>
                </div>
            </div>
            <div class="mockup-stage" aria-label="우리들의 맛집 지도 화면 미리보기">
                <div class="desktop-mockup">
                    <div class="mockup-toolbar">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div class="mockup-content">
                        <div class="mock-map">
                            <span class="pin pin-a"></span>
                            <span class="pin pin-b"></span>
                            <span class="pin pin-c"></span>
                            <div class="route-line"></div>
                        </div>
                        <div class="mock-sidebar">
                            <span class="badge">추천 점수</span>
                            <strong>4.62</strong>
                            <p>우리 그룹의 평점, 재방문 의사, 상황 태그를 조합합니다.</p>
                            <div class="mini-list">
                                <span>약속</span>
                                <span>조용한 곳</span>
                                <span>재방문 높음</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="phone-mockup">
                    <span class="phone-bar"></span>
                    <strong>오늘 뭐 먹지?</strong>
                    <p>후보 12곳 중 우리 취향에 맞는 장소를 추천합니다.</p>
                    <button type="button">추천 보기</button>
                </div>
            </div>
        </section>

        <section id="features" class="feature-grid">
            <article>
                <h3>그룹 맛집 지도</h3>
                <p>사용자가 만든 그룹 단위로 같은 장소도 다른 상태와 평가를 저장합니다.</p>
            </article>
            <article>
                <h3>취향 궁합 평가</h3>
                <p>맛, 가격, 분위기, 접근성, 재방문 의사를 구성원별로 기록합니다.</p>
            </article>
            <article>
                <h3>상황별 추천</h3>
                <p>약속, 함께 외식, 가성비, 조용한 곳 같은 상황 태그로 후보를 추천합니다.</p>
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
            <pre class="code-block"><code>추천 점수 = 구성원 평균 점수 * 0.6
          + 재방문 점수 * 0.3
          + 태그 일치 점수 * 0.5</code></pre>
        </section>
    </main>
    <?php else: ?>
    <main class="app-shell">
        <?php if (!$hasKakaoJsKey): ?>
            <section class="notice">
                <strong>카카오 JavaScript 키가 아직 없습니다.</strong>
                <span><code>config.php</code>에 키를 입력하면 지도가 활성화됩니다.</span>
            </section>
        <?php endif; ?>

        <section id="map-panel" class="app-workspace">
            <div class="map-sticky-panel">
                <div id="map" class="map-box app-map">
                    <p>카카오맵 API 키를 설정하면 이 영역에 지도가 표시됩니다.</p>
                </div>
                <div id="map-fab-dock" class="map-fab-dock" data-mode="manual">
                    <div class="record-quick-actions" hidden>
                        <button type="button" data-action="add-note">기록 추가</button>
                        <button type="button" data-action="edit-note">수정</button>
                    </div>
                    <button type="button" class="map-fab" data-action="primary-map-action" aria-label="지도에서 직접 추가">
                        <span id="map-fab-symbol" aria-hidden="true">+</span>
                    </button>
                </div>
            </div>

            <aside class="app-side-panel">
                <form class="search-panel app-search-panel" id="place-search-form">
                    <div>
                        <label for="keyword">장소 검색</label>
                        <input id="keyword" name="keyword" type="search" placeholder="예: 홍대 파스타, 강남 카페">
                    </div>
                    <div>
                        <label for="category">카테고리</label>
                        <select id="category" name="category">
                            <option value="">전체</option>
                            <option value="FD6">음식점</option>
                            <option value="CE7">카페</option>
                        </select>
                    </div>
                    <button type="submit">검색</button>
                </form>

                <section class="side-section results-section">
                    <div class="section-heading">
                        <h3>검색 결과</h3>
                        <span id="result-count">0개</span>
                    </div>
                    <ul id="place-results">
                        <li class="empty">검색어를 입력하면 장소 후보가 표시됩니다.</li>
                    </ul>
                </section>
            </aside>
        </section>

        <div id="place-note-modal" class="modal-backdrop" hidden>
            <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="place-note-title">
                <div class="modal-header">
                    <div>
                        <p class="eyebrow">Culinary Note</p>
                        <h2 id="place-note-title">미식 기록 작성</h2>
                    </div>
                    <button type="button" class="icon-button" data-action="close-note-modal" aria-label="닫기">×</button>
                </div>
                <form class="note-form">
                    <div class="file-upload-control">
                        <span class="form-label">사진</span>
                        <label class="file-upload-button" for="note-photo">
                            <span>사진 선택</span>
                            <small id="note-photo-name">선택된 파일 없음 · 여러 장 선택하거나 드롭해서 업로드</small>
                        </label>
                        <input id="note-photo" class="visually-hidden" name="photos[]" type="file" accept="image/*" multiple>
                        <div id="note-photo-preview" class="file-upload-preview" hidden>
                            <div id="note-photo-preview-list" class="file-upload-preview-list" aria-label="업로드한 사진 미리보기"></div>
                        </div>
                    </div>

                    <div>
                        <label for="note-menu">대표 메뉴</label>
                        <input id="note-menu" name="menu" type="text" placeholder="예: 쇼유라멘, 트러플 감자튀김">
                    </div>

                    <div>
                        <label for="note-text">미식 기록</label>
                        <textarea id="note-text" name="note" rows="5" placeholder="맛의 인상, 식감, 향, 같이 먹은 메뉴 조합을 남겨보세요."></textarea>
                    </div>

                    <div class="rating-field">
                        <span class="form-label">별점</span>
                        <input id="note-rating" name="rating" type="hidden" value="">
                        <div class="star-rating" data-rating-step="0.5" aria-label="별점 선택">
                            <button type="button" data-rating="0.5" aria-label="0.5점" aria-pressed="false"></button>
                            <button type="button" data-rating="1" aria-label="1점" aria-pressed="false"></button>
                            <button type="button" data-rating="1.5" aria-label="1.5점" aria-pressed="false"></button>
                            <button type="button" data-rating="2" aria-label="2점" aria-pressed="false"></button>
                            <button type="button" data-rating="2.5" aria-label="2.5점" aria-pressed="false"></button>
                            <button type="button" data-rating="3" aria-label="3점" aria-pressed="false"></button>
                            <button type="button" data-rating="3.5" aria-label="3.5점" aria-pressed="false"></button>
                            <button type="button" data-rating="4" aria-label="4점" aria-pressed="false"></button>
                            <button type="button" data-rating="4.5" aria-label="4.5점" aria-pressed="false"></button>
                            <button type="button" data-rating="5" aria-label="5점" aria-pressed="false"></button>
                        </div>
                        <div class="rating-meta">
                            <span id="note-rating-label" class="rating-value">선택 안 함</span>
                            <span class="rating-help">별을 탭해서 0.5점 단위로 선택</span>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="button secondary" data-action="close-note-modal">취소</button>
                        <button type="button" class="button primary">저장 준비</button>
                    </div>
                </form>
            </section>
        </div>
    </main>
    <?php endif; ?>

    <script>
        window.TASTEMAP_HAS_KAKAO = <?= $hasKakaoJsKey ? 'true' : 'false' ?>;
    </script>
    <script src="<?= tastemap_asset_versioned('assets/js/app.js') ?>"></script>
</body>
</html>
