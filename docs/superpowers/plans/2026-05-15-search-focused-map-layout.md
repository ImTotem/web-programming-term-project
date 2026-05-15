# Search-Focused Map Layout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the logged-in home screen focus on map search results, with secondary saved/stats work moved out of the main panel.

**Architecture:** Keep the existing two-column app shell. The right side becomes a search form plus one large scrollable results section; place management actions are represented as buttons that later open modals. Search result item clicks focus the Kakao map and selected marker.

**Tech Stack:** PHP 7.4, Kakao Maps JavaScript SDK, plain JavaScript, CSS, Node.js contract test.

---

### Task 1: Search-Centered App Panel

**Files:**
- Modify: `index.php`
- Modify: `assets/css/style.css`
- Test: `tests/app_layout_contract.test.js`

- [x] **Step 1: Write the failing test**

Run: `node tests/app_layout_contract.test.js`

Expected: FAIL because category does not include `전체`, saved/stats sections still exist, and result focus logic is missing.

- [x] **Step 2: Update app markup**

Remove `saved-panel` and `stats-panel` from logged-in `index.php`. Add `<option value="">전체</option>` before food/cafe categories. Add `results-section` to the search results section.

- [x] **Step 3: Expand result space**

Change `.app-side-panel` to use `grid-template-rows: auto minmax(0, 1fr)` so the result section gets the remaining height. Make `.results-section` scroll internally.

- [x] **Step 4: Add place focus behavior**

In `assets/js/app.js`, add `focusPlace(index, place)`. Search result items should be clickable, move the map center to the place, mark the clicked list item active, lift the marker z-index, and show an info window.

- [x] **Step 5: Verify**

Run:

```bash
node tests/app_layout_contract.test.js
node --check assets/js/app.js
docker compose exec web php -l /var/www/html/tastemap/index.php
```

Then verify in the browser that results have more vertical space and clicking a result focuses the map.
