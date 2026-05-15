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
    !/id="map-action-panel"/.test(index) && /id="map-fab-dock"/.test(index),
    'map actions should use a bottom-right floating action button instead of a wide panel'
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
    /data-action="primary-map-action"/.test(index) && /id="place-note-modal"/.test(index),
    'culinary note, menu photo, and rating should be collected in one modal'
);

assert(
    !/Kakao Local API는 카테고리 목록 조회 API를 제공하지 않습니다/.test(index),
    'category source limitation copy should not be shown in the app screen'
);

assert(
    /function togglePlaceSelection/.test(app) && /function clearPlaceSelection/.test(app),
    'clicking a selected result again should cancel the selection'
);

assert(
    /record-quick-actions/.test(index) && /data-action="add-note"/.test(index) && /data-action="edit-note"/.test(index),
    'places with existing records should expose add and edit buttons next to the circular action'
);

assert(
    /\.map-fab-dock/.test(css) && /border-radius:\s*50%/.test(css),
    'map action should be a circular bottom-right button'
);

assert(
    /grid-template-rows:\s*auto minmax\(0,\s*1fr\)/.test(css),
    'side panel should reserve most vertical space for search results'
);

console.log('app layout contract passed');
