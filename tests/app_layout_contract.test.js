const assert = require('assert');
const fs = require('fs');

const index = fs.readFileSync('index.php', 'utf8');
const css = fs.readFileSync('assets/css/style.css', 'utf8');
const app = fs.readFileSync('assets/js/app.js', 'utf8');
const profile = fs.existsSync('profile.php') ? fs.readFileSync('profile.php', 'utf8') : '';

assert(
    /<option value="">전체<\/option>/.test(index),
    'search category should include an optional "전체" choice'
);

assert(
    !/id="saved-panel"/.test(index) && !/id="stats-panel"/.test(index),
    'saved candidates and group summary should move out of the main map screen'
);

assert(
    /class="side-section results-section"/.test(index),
    'search results should be the primary side-panel section'
);

assert(
    /function focusPlace/.test(app),
    'search result items should focus the selected place on the map'
);

assert(
    !/data-action="save-place"/.test(app),
    'search result list should not show a save action button'
);

assert(
    /id="result-count"/.test(index) && /updateResultCount/.test(app),
    'search results should show the total result count'
);

assert(
    /id="map-action-panel"/.test(index) && /data-action="manual-place-mode"/.test(index),
    'map should contain contextual place actions and manual location mode'
);

assert(
    /profile-menu/.test(index) && /profile.php/.test(index) && profile.includes('내정보'),
    'user menu should link to a profile page'
);

assert(
    /height:\s*52px/.test(css),
    'header should be thinner than the previous 64px bar'
);

assert(
    /미식 기록/.test(index) && /메뉴 사진/.test(index),
    'map actions should frame visits as culinary notes and menu photos'
);

assert(
    /Kakao Local API는 카테고리 목록 조회 API를 제공하지 않습니다/.test(index),
    'category source limitation should be documented in the UI copy'
);

assert(
    /data-action="write-visit-note"/.test(index) && /data-action="upload-menu-photo"/.test(index),
    'review and photo actions should live in the map context, not the results list'
);

assert(
    /grid-template-rows:\s*auto minmax\(0,\s*1fr\)/.test(css),
    'side panel should reserve most vertical space for search results'
);

console.log('app layout contract passed');
