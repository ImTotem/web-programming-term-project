const assert = require('assert');
const fs = require('fs');

const index = fs.readFileSync('index.php', 'utf8');
const css = fs.readFileSync('assets/css/style.css', 'utf8');
const app = fs.readFileSync('assets/js/app.js', 'utf8');

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
    /data-action="save-place"/.test(app),
    'search result items should expose a save action placeholder'
);

assert(
    /grid-template-rows:\s*auto minmax\(0,\s*1fr\)/.test(css),
    'side panel should reserve most vertical space for search results'
);

console.log('app layout contract passed');
