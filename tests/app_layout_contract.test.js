const assert = require('assert');
const fs = require('fs');

const index = fs.readFileSync('index.php', 'utf8');
const css = fs.readFileSync('assets/css/style.css', 'utf8');
const app = fs.readFileSync('assets/js/app.js', 'utf8');
const profile = fs.existsSync('profile.php') ? fs.readFileSync('profile.php', 'utf8') : '';
const schema = fs.readFileSync('db/schema.sql', 'utf8');
const saveNoteApi = fs.existsSync('api/save_note.php') ? fs.readFileSync('api/save_note.php', 'utf8') : '';
const notes = fs.existsSync('includes/notes.php') ? fs.readFileSync('includes/notes.php', 'utf8') : '';

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
    /align-items:\s*center/.test(css) && /#result-count[\s\S]*line-height:\s*1/.test(css) && /\.side-section \.section-heading h3[\s\S]*margin:\s*0/.test(css),
    'search result heading and total count should be vertically aligned'
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
    /data-action="save-note"/.test(index) && /bindNoteForm/.test(app) && /api\/save_note\.php/.test(app),
    'culinary note form should submit to a save API'
);

assert(
    /menu_name VARCHAR\(150\)/.test(schema) && /rating DECIMAL\(2,1\)/.test(schema),
    'visits table should store the representative menu and half-point rating'
);

assert(
    /notes_save_visit/.test(saveNoteApi) && /INSERT INTO visits/.test(notes) && /INSERT INTO visit_photos/.test(notes) && /INSERT INTO restaurants/.test(notes),
    'save note API should persist restaurant, visit, and uploaded photos'
);

assert(
    index.indexOf('id="note-photo"') < index.indexOf('id="note-menu"'),
    'photo upload should appear before menu and note fields'
);

assert(
    /file-upload-control/.test(index) && /file-upload-button/.test(css) && /bindFileUpload/.test(app),
    'file input should use a custom designed upload control'
);

assert(
    /<span class="form-label">사진<\/span>/.test(index) && !/<span class="form-label">메뉴 사진<\/span>/.test(index),
    'photo upload label should be "사진"'
);

assert(
    /file-upload-header/.test(index + css) && /file-upload-control\s*\{[^}]*width:\s*100%/s.test(css) && /file-upload-button/.test(index) && !/file-drop-hint/.test(index) && !/min-height:\s*104px/.test(css),
    'photo upload button should sit beside the label without a large centered drop zone'
);

assert(
    /multiple/.test(index) && /name="photos\[\]"/.test(index) && /note-photo-preview-list/.test(index),
    'photo upload should accept more than one image'
);

assert(
    /note-photo-preview/.test(index) && /showPhotoPreview/.test(app) && /photoPreviewUrls/.test(app) && /grid-auto-columns:\s*104px/.test(css),
    'uploaded photos should render multiple resized previews in the modal'
);

assert(
    /overflow-x:\s*auto/.test(css) && /grid-auto-flow:\s*column/.test(css) && /grid-auto-columns:\s*104px/.test(css),
    'uploaded photo previews should scroll horizontally instead of wrapping downward'
);

assert(
    /selectedPhotoFiles/.test(app) && /appendSelectedPhotos/.test(app) && /syncPhotoInput/.test(app) && /new DataTransfer/.test(app),
    'newly selected photos should be appended instead of replacing previous selections'
);

assert(
    /removePhotoAt/.test(app) && /data-action="remove-photo"/.test(app) && /photo-preview-remove/.test(css),
    'uploaded photo previews should expose an x button for removing an image'
);

assert(
    /movePhoto/.test(app) && /draggable/.test(app) && /bindPhotoPreviewActions/.test(app) && /photo-preview-item/.test(css),
    'uploaded photo previews should support drag reordering'
);

assert(
    /is-dragging/.test(app + css) && /note-photo-preview/.test(app) && !/note-photo-name/.test(index + app),
    'photo upload control should guide and support dropping files'
);

assert(
    /star-rating/.test(index) && /data-rating-step="0.5"/.test(index) && /bindStarRating/.test(app),
    'rating should use stars with half-point increments'
);

assert(
    /setRatingValue/.test(app) && /aria-pressed/.test(index) && !/ratingFromPointer/.test(app) && !/getBoundingClientRect/.test(app) && /grid-template-columns:\s*repeat\(10,\s*20px\)/.test(css) && /button:nth-of-type\(10\)/.test(css) && /button::before/.test(css) && /button:nth-of-type\(even\)::before/.test(css),
    'star rating should use deterministic tap targets aligned to the visible stars'
);

assert(
    /별을 탭해서 0\.5점 단위로 선택/.test(index) && /touch-action:\s*manipulation/.test(css) && /rating-input-area/.test(index + css),
    'star rating should guide touch-style selection'
);

assert(
    /width:\s*min\(100%,\s*480px\)/.test(css),
    'modal input controls should be centered without centering section titles and labels'
);

assert(
    !/<select id="note-rating"/.test(index),
    'rating should not use a native dropdown'
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
    /data-speed-dial/.test(index) && /aria-expanded/.test(app) && /map-speed-dial-action/.test(css),
    'recorded place actions should use a speed dial style FAB'
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
