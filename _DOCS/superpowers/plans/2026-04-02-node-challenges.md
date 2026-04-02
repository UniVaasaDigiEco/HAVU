# Node Challenges Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add optional quiz/password challenges to route nodes so players must answer correctly before marking a node as visited.

**Architecture:** A nullable `challenge_data` JSON column is added to `nodes`. The Node PHP class exposes it and it flows automatically through `Route::toJavaScript()` to the client. All challenge verification is client-side JavaScript. A shared `js/admin-challenge-panel.js` file handles the admin editor UI for both new-route and edit-route pages. A `REQUIRE_GPS_PROXIMITY` constant controls whether GPS proximity is enforced (set false for dev, true for prod).

**Tech Stack:** PHP 8.4, MySQL 8.0, vanilla JS, Leaflet.js, Bootstrap 5, jQuery, XAMPP (no build step — edit and refresh).

---

## File Map

| File | Action | What changes |
|---|---|---|
| `_SQL/add_challenge_data_to_nodes.sql` | Create | Migration: adds `challenge_data JSON NULL` column |
| `classes/node.class.php` | Modify | Add `challenge_data` property, getter, SELECT, toArray |
| `config/constants.php` | Modify | Add `REQUIRE_GPS_PROXIMITY` boolean constant |
| `js/admin-challenge-panel.js` | Create | Shared challenge panel JS for both admin editors |
| `pages/admin/new-route.php` | Modify | Challenge panel HTML + include shared JS + wire into node save |
| `pages/admin/edit-route.php` | Modify | Same as new-route, plus populate panel from loaded node data |
| `actions/create_route.php` | Modify | Add `challenge_data` to node INSERT |
| `actions/update-route.php` | Modify | Add `challenge_data` to node INSERT |
| `pages/game.php` | Modify | GPS constant, challenge in routeNodes, buildPopupContent, Levenshtein, submitChallenge, proximity tracking |

---

## Task 1: SQL Migration

**Files:**
- Create: `_SQL/add_challenge_data_to_nodes.sql`

- [ ] **Step 1: Create the migration file**

```sql
-- _SQL/add_challenge_data_to_nodes.sql
ALTER TABLE nodes ADD COLUMN challenge_data JSON NULL DEFAULT NULL;
```

- [ ] **Step 2: Run the migration**

Open phpMyAdmin → select `jansoftw_havu` database → SQL tab → paste and execute.

Or via MySQL CLI:
```bash
mysql -u root jansoftw_havu < _SQL/add_challenge_data_to_nodes.sql
```

- [ ] **Step 3: Verify**

In phpMyAdmin, open the `nodes` table structure. Confirm a `challenge_data` column exists with type `json`, nullable, default NULL.

- [ ] **Step 4: Commit**

```bash
git add _SQL/add_challenge_data_to_nodes.sql
git commit -m "feat: add challenge_data column to nodes table"
```

---

## Task 2: Update Node Class

**Files:**
- Modify: `classes/node.class.php`

The `Node` class loads data in its constructor via a prepared statement. Adding `challenge_data` requires updating the SELECT query, the `bind_result` call, assigning the property, the getter, and `toArray()`. Because `Route::toArray()` calls `Node::toArray()` for each node, this single change propagates `challenge_data` all the way to `Route::toJavaScript()` with no other PHP changes needed.

- [ ] **Step 1: Add the property declaration**

In `classes/node.class.php`, after line 17 (`private float $longitude;`), add:

```php
    private ?string $challenge_data;
```

- [ ] **Step 2: Update the SELECT query**

Change line 29 from:
```php
        $sql = "SELECT public_id, is_published, publication_date, created_by, created_at, updated_at, title, content, latitude, longitude FROM nodes WHERE id = ?";
```
To:
```php
        $sql = "SELECT public_id, is_published, publication_date, created_by, created_at, updated_at, title, content, latitude, longitude, challenge_data FROM nodes WHERE id = ?";
```

- [ ] **Step 3: Update bind_result**

Change line 47 from:
```php
            $stmt->bind_result($public_id, $is_published, $publication_date, $created_by, $created_at, $updated_at, $title, $content, $latitude, $longitude);
```
To:
```php
            $stmt->bind_result($public_id, $is_published, $publication_date, $created_by, $created_at, $updated_at, $title, $content, $latitude, $longitude, $challenge_data);
```

- [ ] **Step 4: Assign the property**

After line 66 (`$this->longitude = $longitude;`), add:
```php
            $this->challenge_data = $challenge_data;
```

- [ ] **Step 5: Add getter**

After the `getLongitude()` getter (around line 157), add:
```php
    /**
     * @return array|null Decoded challenge data, or null if no challenge
     */
    public function getChallengeData(): ?array
    {
        return $this->challenge_data ? json_decode($this->challenge_data, true) : null;
    }
```

- [ ] **Step 6: Update toArray()**

In the `toArray()` method, add `challenge_data` as the last entry before the closing bracket:
```php
            'challenge_data' => $this->getChallengeData(),
```

The full updated `toArray()` return should look like:
```php
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'is_published' => $this->is_published,
            'publication_date' => $this->publication_date->format('Y-m-d'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'created_by' => $this->created_by,
            'title' => $this->title,
            'content' => $this->content,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'challenge_data' => $this->getChallengeData(),
        ];
```

- [ ] **Step 7: Verify**

Open `http://localhost/HavuGamification/pages/game.php` in the browser. Open the browser console and run:
```js
console.log(routeData.nodes[0].node.challenge_data);
```
Expected output: `null` (no challenges set yet, but the key exists without errors).

If you see a PHP error like "Column not found", the migration in Task 1 didn't run. If you see a JS error, check the PHP error log in `pages/admin/error_log`.

- [ ] **Step 8: Commit**

```bash
git add classes/node.class.php
git commit -m "feat: add challenge_data to Node class"
```

---

## Task 3: Add GPS Proximity Constant

**Files:**
- Modify: `config/constants.php`
- Modify: `pages/game.php`

- [ ] **Step 1: Add constant to config**

In `config/constants.php`, at the end of the file before the closing `?>` (or at the end since there is none), add:

```php
// Set to false during development to skip GPS proximity checks
define('REQUIRE_GPS_PROXIMITY', true);
```

- [ ] **Step 2: Embed constant in game.php**

In `pages/game.php`, in the `<script>` block, after the line `const UPDATE_INTERVAL = 3000;` (around line 84), add:

```js
        const REQUIRE_GPS_PROXIMITY = <?= REQUIRE_GPS_PROXIMITY ? 'true' : 'false' ?>;
```

- [ ] **Step 3: Verify**

Open `http://localhost/HavuGamification/pages/game.php`. In the browser console:
```js
console.log(REQUIRE_GPS_PROXIMITY);
```
Expected: `true` (or `false` if you temporarily set the constant to false for testing).

- [ ] **Step 4: Commit**

```bash
git add config/constants.php pages/game.php
git commit -m "feat: add REQUIRE_GPS_PROXIMITY constant"
```

---

## Task 4: Update create_route.php

**Files:**
- Modify: `actions/create_route.php`

The node INSERT currently has 8 columns. We add `challenge_data` as a 9th. The challenge comes from `$node->challenge` in the submitted JSON (the admin JS will send it; null for nodes with no challenge).

- [ ] **Step 1: Update the node INSERT SQL**

Find the line (around line 68):
```php
        $node_sql = "INSERT INTO nodes (public_id, is_published, publication_date, created_by, title, content, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
```

Replace with:
```php
        $node_sql = "INSERT INTO nodes (public_id, is_published, publication_date, created_by, title, content, latitude, longitude, challenge_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
```

- [ ] **Step 2: Extract challenge data and update bind_param**

Find the block inside the `foreach` (around line 99) where `$node_stmt->bind_param` is called. Before that call, add:

> **Note on format string:** The existing format string `'sissssdd'` has one character per parameter: s=string, i=integer, d=double. Verify by counting characters between the quotes — it must equal the number of `$node_*` variables that follow. The new format string `'sissssdds'` adds one `s` at the end for `$node_challenge`. If the original string differs, adjust accordingly.

```php
            $node_challenge = isset($node->challenge) && $node->challenge !== null
                ? json_encode($node->challenge)
                : null;
```

Then change the `bind_param` call from:
```php
            $node_stmt->bind_param('sissssdd', $node_public_id, $is_published, $formatted_publication_date, $created_by, $node_title, $node_content, $node_latitude, $node_longitude);
```
To:
```php
            // s=public_id, i=is_published, s=pub_date, s=created_by, s=title, s=content, d=lat, d=lng, s=challenge_data
            $node_stmt->bind_param('sissssdds',
                $node_public_id, $is_published, $formatted_publication_date, $created_by,
                $node_title, $node_content, $node_latitude, $node_longitude, $node_challenge
            );
```

- [ ] **Step 3: Verify**

After completing Tasks 6 (new-route.php), create a route with a challenge via the admin UI. In phpMyAdmin, check the `nodes` table — the `challenge_data` column should contain valid JSON for nodes that have a challenge, and NULL for nodes that don't.

*(Come back to this verification step after Task 6.)*

- [ ] **Step 4: Commit**

```bash
git add actions/create_route.php
git commit -m "feat: persist challenge_data when creating routes"
```

---

## Task 5: Update update-route.php

**Files:**
- Modify: `actions/update-route.php`

Same change as Task 4. The update action deletes old nodes and inserts new ones, so the INSERT path is identical.

- [ ] **Step 1: Update the node INSERT SQL**

Find the line (around line 114):
```php
        $node_sql = 'INSERT INTO nodes (public_id, is_published, publication_date, created_by, title, content, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
```

Replace with:
```php
        $node_sql = 'INSERT INTO nodes (public_id, is_published, publication_date, created_by, title, content, latitude, longitude, challenge_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
```

- [ ] **Step 2: Extract challenge data and update bind_param**

Inside the `foreach` loop (around line 141), before the `bind_param` call, add:

> **Note on format string:** Same as Task 4 — verify the existing string character count matches the parameter count before updating it.

```php
            $node_challenge = isset($node->challenge) && $node->challenge !== null
                ? json_encode($node->challenge)
                : null;
```

Change the `bind_param` call from:
```php
            $node_stmt->bind_param('sissssdd', $node_public_id, $is_published, $formatted_publication_date, $created_by, $node_title, $node_content, $node_latitude, $node_longitude);
```
To:
```php
            // s=public_id, i=is_published, s=pub_date, s=created_by, s=title, s=content, d=lat, d=lng, s=challenge_data
            $node_stmt->bind_param('sissssdds',
                $node_public_id, $is_published, $formatted_publication_date, $created_by,
                $node_title, $node_content, $node_latitude, $node_longitude, $node_challenge
            );
```

- [ ] **Step 3: Commit**

```bash
git add actions/update-route.php
git commit -m "feat: persist challenge_data when updating routes"
```

---

## Task 6: Shared Admin Challenge Panel JS

**Files:**
- Create: `js/admin-challenge-panel.js`

This file contains all the challenge panel logic shared between `new-route.php` and `edit-route.php`. It exposes functions via the module-level scope (no bundler, just global functions). It depends on Bootstrap being loaded for `alert()` and on the page having the HTML elements from Task 7/8.

- [ ] **Step 1: Create js/admin-challenge-panel.js**

```js
// Challenge panel state
let currentChallengeType = 'none';
let challengeOptions = ['', ''];
let challengeCorrectIndex = 0;

/**
 * Switch the challenge type and show/hide relevant fields.
 * @param {string} type - 'none' | 'multiple_choice' | 'text'
 */
function setChallengeType(type) {
    currentChallengeType = type;

    const btnNone = document.getElementById('challengeTypeNone');
    const btnMC   = document.getElementById('challengeTypeMC');
    const btnText = document.getElementById('challengeTypeText');

    btnNone.className = 'btn btn-sm ' + (type === 'none'             ? 'btn-secondary'      : 'btn-outline-secondary');
    btnMC.className   = 'btn btn-sm ' + (type === 'multiple_choice'  ? 'btn-warning active'  : 'btn-outline-secondary');
    btnText.className = 'btn btn-sm ' + (type === 'text'             ? 'btn-warning active'  : 'btn-outline-secondary');

    document.getElementById('challengeFieldsMC').style.display   = type === 'multiple_choice' ? '' : 'none';
    document.getElementById('challengeFieldsText').style.display = type === 'text'            ? '' : 'none';
}

/**
 * Re-render the list of multiple-choice option rows.
 */
function renderChallengeOptions() {
    const container = document.getElementById('challengeOptions');
    if (!container) return;

    container.innerHTML = challengeOptions.map((opt, i) => `
        <div class="d-flex align-items-center gap-2 mb-2" id="challengeOption_${i}">
            <input type="radio" name="challengeCorrect" ${i === challengeCorrectIndex ? 'checked' : ''}
                   onchange="challengeCorrectIndex = ${i}" style="accent-color:#198754;transform:scale(1.2)">
            <input type="text" class="form-control form-control-sm"
                   value="${escapeHtml(opt)}"
                   oninput="challengeOptions[${i}] = this.value"
                   placeholder="Vaihtoehto ${i + 1}">
            ${challengeOptions.length > 2
                ? `<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeChallengeOption(${i})">✕</button>`
                : '<div style="width:31px"></div>'}
        </div>
    `).join('');

    const addBtn = document.getElementById('addOptionBtn');
    if (addBtn) addBtn.style.display = challengeOptions.length >= 4 ? 'none' : '';
}

function addChallengeOption() {
    if (challengeOptions.length >= 4) return;
    challengeOptions.push('');
    renderChallengeOptions();
}

function removeChallengeOption(index) {
    if (challengeOptions.length <= 2) return;
    challengeOptions.splice(index, 1);
    if (challengeCorrectIndex >= challengeOptions.length) challengeCorrectIndex = 0;
    renderChallengeOptions();
}

/**
 * Collect and validate current challenge panel state.
 * @returns {object|null|false} Challenge object, null (no challenge), or false (validation failed).
 */
function getChallengeData() {
    if (currentChallengeType === 'none') return null;

    if (currentChallengeType === 'multiple_choice') {
        const question = document.getElementById('challengeQuestion').value.trim();
        if (!question) { alert('Lisää kysymys haastetta varten.'); return false; }
        const opts = challengeOptions.map(o => o.trim());
        if (opts.some(o => !o)) { alert('Täytä kaikki vastausvaihtoehdot.'); return false; }
        return {
            type: 'multiple_choice',
            question: question,
            options: opts,
            correct_index: challengeCorrectIndex
        };
    }

    if (currentChallengeType === 'text') {
        const question = document.getElementById('challengeQuestionText').value.trim();
        const answer   = document.getElementById('challengeAnswer').value.trim();
        if (!question || !answer) { alert('Lisää kysymys ja oikea vastaus.'); return false; }
        return { type: 'text', question: question, answer: answer };
    }

    return null;
}

/**
 * Reset the challenge panel to "no challenge" state.
 */
function resetChallengePanel() {
    currentChallengeType = 'none';
    challengeOptions = ['', ''];
    challengeCorrectIndex = 0;

    const q  = document.getElementById('challengeQuestion');
    const qt = document.getElementById('challengeQuestionText');
    const a  = document.getElementById('challengeAnswer');
    if (q)  q.value  = '';
    if (qt) qt.value = '';
    if (a)  a.value  = '';

    setChallengeType('none');
    renderChallengeOptions();
}

/**
 * Populate the challenge panel from an existing challenge object.
 * @param {object|null} challenge
 */
function populateChallengePanel(challenge) {
    if (!challenge) { resetChallengePanel(); return; }

    if (challenge.type === 'multiple_choice') {
        challengeOptions = challenge.options ? [...challenge.options] : ['', ''];
        challengeCorrectIndex = challenge.correct_index || 0;
        setChallengeType('multiple_choice');
        const q = document.getElementById('challengeQuestion');
        if (q) q.value = challenge.question || '';
        renderChallengeOptions();
    } else if (challenge.type === 'text') {
        setChallengeType('text');
        const qt = document.getElementById('challengeQuestionText');
        const a  = document.getElementById('challengeAnswer');
        if (qt) qt.value = challenge.question || '';
        if (a)  a.value  = challenge.answer   || '';
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add js/admin-challenge-panel.js
git commit -m "feat: add shared admin challenge panel JS"
```

---

## Task 7: Challenge Panel in new-route.php

**Files:**
- Modify: `pages/admin/new-route.php`

`new-route.php` has a `#nodeEditor` card. The challenge panel HTML goes inside its `.card-body`, after the `node_content` textarea block and its `<small>` tag. Several JS functions also need updating.

- [ ] **Step 1: Add the shared JS include**

In `pages/admin/new-route.php`, after the last `<script src="...">` tag (the summernote lang file), add:

```html
<script src="../../js/admin-challenge-panel.js"></script>
```

- [ ] **Step 2: Add challenge panel HTML**

Inside the `#nodeEditor` card's `.card-body`, find the closing `</div>` of the `node_content` field block (the one containing `<small>Lyhyt sisältö rastille...</small>`). After that entire `<div class="mb-3">` block, add:

```html
                        <!-- Challenge panel -->
                        <div class="mb-3">
                            <div class="card border-warning">
                                <div class="card-header bg-warning bg-opacity-25 py-2">
                                    <strong>⚡ Haaste <small class="text-muted fw-normal">(valinnainen)</small></strong>
                                    <small class="d-block text-muted mt-1">Pelaajan täytyy vastata oikein ennen kuin rasti voidaan merkitä käydyksi</small>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="fw-semibold small text-uppercase text-muted mb-2">Haasteen tyyppi</div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-secondary" id="challengeTypeNone" onclick="setChallengeType('none')">Ei haastetta</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="challengeTypeMC" onclick="setChallengeType('multiple_choice')">Monivalinta</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="challengeTypeText" onclick="setChallengeType('text')">Tekstivastaus</button>
                                        </div>
                                    </div>
                                    <!-- Multiple choice fields -->
                                    <div id="challengeFieldsMC" style="display:none">
                                        <div class="mb-3">
                                            <label class="form-label">Kysymys <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="challengeQuestion" placeholder="Esim. Mitä puulajia täällä on eniten?">
                                        </div>
                                        <div class="fw-semibold small text-uppercase text-muted mb-2">Vastausvaihtoehdot <span class="fw-normal text-muted text-lowercase">(merkitse oikea)</span></div>
                                        <div id="challengeOptions"></div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-1" onclick="addChallengeOption()" id="addOptionBtn">+ Lisää vaihtoehto</button>
                                    </div>
                                    <!-- Text answer fields -->
                                    <div id="challengeFieldsText" style="display:none">
                                        <div class="mb-3">
                                            <label class="form-label">Kysymys <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="challengeQuestionText" placeholder="Esim. Mikä on lähimmän lammen nimi?">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Oikea vastaus <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="challengeAnswer" placeholder="Oikea vastaus">
                                            <div class="form-text">💡 Kirjoitusvirheet hyväksytään automaattisesti (n. 70% osuma riittää)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
```

- [ ] **Step 3: Add `challenge: null` to the node object in addNode()**

In the `addNode()` function, find where the `node` object is constructed:
```js
        const node = {
            lat: Number(lat),
            lng: Number(lng),
            title: title || `Node ${nodeNumber}`,
            content: content || ''
        };
```

Add `challenge: null`:
```js
        const node = {
            lat: Number(lat),
            lng: Number(lng),
            title: title || `Node ${nodeNumber}`,
            content: content || '',
            challenge: null
        };
```

- [ ] **Step 4: Update saveNodeEdit() to collect challenge**

Find `saveNodeEdit()`. Replace the body with:
```js
    function saveNodeEdit() {
        const index = parseInt(document.getElementById('editNodeIndex').value, 10);
        const title = document.getElementById('node_title').value.trim();
        const content = $('#node_content').summernote('code');
        const challenge = getChallengeData();

        if (!title) {
            alert('Rastin nimi on pakollinen');
            return;
        }
        if (challenge === false) return; // getChallengeData() showed its own alert

        nodes[index].title = title;
        nodes[index].content = content;
        nodes[index].challenge = challenge;
        markers[index].setPopupContent(buildPopupContent(nodes[index]));

        updateNodesList();
        cancelNodeEdit();
    }
```

Note: `buildPopupContent` will be added in Task 9 (game.php). For now the admin map popup just shows a placeholder — that's fine; the admin map is for placing nodes, not playing.

Actually, since admin pages have their own popup builder (not the game's `buildPopupContent`), update the admin's `buildPopupContent` instead:

```js
    function buildPopupContent(node) {
        const challengeLabel = node.challenge
            ? `<div class="mt-1"><small class="text-warning">⚡ Haaste: ${escapeHtml(node.challenge.question)}</small></div>`
            : '';
        return `<b>${escapeHtml(node.title)}</b><div class="mt-1">${node.content || '<em>Ei sisältöä</em>'}</div>${challengeLabel}`;
    }
```

- [ ] **Step 5: Update cancelNodeEdit() to reset challenge panel**

Find `cancelNodeEdit()`. Add `resetChallengePanel();` at the start:
```js
    function cancelNodeEdit() {
        resetChallengePanel();
        document.getElementById('nodeEditor').style.display = 'none';
        document.getElementById('editNodeIndex').value = '';
        document.getElementById('node_title').value = '';
        $('#node_content').summernote('code', '');
    }
```

- [ ] **Step 6: Update editNode() to populate challenge panel**

Find `editNode(index)`. After the lines that set `node_title`, `node_lat`, `node_lng`, `node_content`, add:
```js
        populateChallengePanel(node.challenge);
```

- [ ] **Step 7: Initialize challenge panel on DOMContentLoaded**

In the `DOMContentLoaded` callback, add `renderChallengeOptions();` after `initEditor();`:
```js
        initMap();
        initEditor();
        renderChallengeOptions();
```

- [ ] **Step 8: Verify**

1. Open `http://localhost/HavuGamification/pages/admin/new-route.php`
2. Click anywhere on the map to add a node — the node editor should appear with the yellow challenge panel at the bottom
3. Click "Monivalinta" — confirm question field and two option fields appear with a radio button each
4. Click "+ Lisää vaihtoehto" — confirm a third option row appears
5. Click "Tekstivastaus" — confirm question and answer fields appear
6. Click "Ei haastetta" — confirm all fields hide
7. Set a multiple-choice challenge, save the node — confirm the popup on the map shows "⚡ Haaste: ..."
8. Re-open the node editor — confirm the challenge panel is populated with the saved values

- [ ] **Step 9: Commit**

```bash
git add pages/admin/new-route.php
git commit -m "feat: add challenge panel to new-route node editor"
```

---

## Task 8: Challenge Panel in edit-route.php

**Files:**
- Modify: `pages/admin/edit-route.php`

This is the same as Task 7, with one extra step: `addNode()` must accept an existing `challenge` parameter so `loadSelectedRouteData()` can populate it from the loaded route.

- [ ] **Step 1: Add JS include (same as Task 7 Step 1)**

After the last `<script src="...">` tag, add:
```html
<script src="../../js/admin-challenge-panel.js"></script>
```

- [ ] **Step 2: Add challenge panel HTML (same HTML as Task 7 Step 2)**

Add the identical HTML block to the `#nodeEditor` `.card-body`, after the `node_content` field block.

- [ ] **Step 3: Update addNode() signature to accept challenge**

Find `addNode(lat, lng, title = '', content = '', openEditor = true)`. Change the signature and node object:

```js
    function addNode(lat, lng, title = '', content = '', challenge = null, openEditor = true) {
        // ...existing code...
        const node = {
            lat: Number(lat),
            lng: Number(lng),
            title: title || `Node ${nodeNumber}`,
            content: content || '',
            challenge: challenge || null
        };
```

- [ ] **Step 4: Update loadSelectedRouteData() to pass challenge**

Find `loadSelectedRouteData()`. In the `routeNodes.forEach` callback, change:
```js
            addNode(node.latitude, node.longitude, node.title || '', node.content || '', false);
```
To:
```js
            addNode(node.latitude, node.longitude, node.title || '', node.content || '', node.challenge_data || null, false);
```

- [ ] **Step 5–8: Apply the same changes as Task 7 Steps 4–7**

Apply each of these to `edit-route.php` (identical code to Task 7):
- Update `saveNodeEdit()` (Task 7 Step 4)
- Update `cancelNodeEdit()` (Task 7 Step 5)
- Update `editNode()` (Task 7 Step 6)
- Add `renderChallengeOptions()` to `DOMContentLoaded` (Task 7 Step 7)
- Add `buildPopupContent()` (Task 7 Step 4 note)

- [ ] **Step 6: Verify**

1. Create a route with at least one challenge node via `new-route.php` (from Task 7)
2. Open `edit-route.php` and load that route
3. Click the node with the challenge — confirm the challenge panel pre-populates with the saved values
4. Change the challenge type and save — confirm the updated challenge saves correctly (check phpMyAdmin)

- [ ] **Step 7: Commit**

```bash
git add pages/admin/edit-route.php
git commit -m "feat: add challenge panel to edit-route node editor"
```

---

## Task 9: Game Page — Challenge Gameplay

**Files:**
- Modify: `pages/game.php`

This is the largest single task. We add: Levenshtein fuzzy match, a `buildPopupContent()` function, challenge rendering in popups, GPS proximity tracking per-node, `submitChallenge()`, and updated `markAsVisited()`.

The `<script>` block in `game.php` starts around line 81 and ends around line 457. All changes are within this block.

- [ ] **Step 1: Add challenge and proximity fields to routeNodes**

Find the `routeNodes` mapping (around line 117). Replace:
```js
        const routeNodes = routeData.nodes.map((nodeData, _index) => ({
            id: nodeData.node.id,
            name: nodeData.node.title,
            lat: parseFloat(nodeData.node.latitude),
            lng: parseFloat(nodeData.node.longitude),
            description: nodeData.node.content,
            visited: false,
            order_number: nodeData.order_number
        }));
```
With:
```js
        const routeNodes = routeData.nodes.map((nodeData, _index) => ({
            id: nodeData.node.id,
            name: nodeData.node.title,
            lat: parseFloat(nodeData.node.latitude),
            lng: parseFloat(nodeData.node.longitude),
            description: nodeData.node.content,
            challenge: nodeData.node.challenge_data || null,
            challengeSolved: false,
            inProximity: false,
            visited: false,
            order_number: nodeData.order_number
        }));
```

- [ ] **Step 2: Add Levenshtein and similarity functions**

Add these two functions immediately after the `routeNodes` mapping and before any other function definitions:

```js
        function levenshtein(a, b) {
            const m = a.length, n = b.length;
            const dp = [];
            for (let i = 0; i <= m; i++) {
                dp[i] = new Array(n + 1).fill(0);
                dp[i][0] = i;
            }
            for (let j = 0; j <= n; j++) dp[0][j] = j;
            for (let i = 1; i <= m; i++) {
                for (let j = 1; j <= n; j++) {
                    dp[i][j] = a[i - 1] === b[j - 1]
                        ? dp[i - 1][j - 1]
                        : 1 + Math.min(dp[i - 1][j - 1], dp[i - 1][j], dp[i][j - 1]);
                }
            }
            return dp[m][n];
        }

        function textSimilarity(a, b) {
            a = a.toLowerCase().trim();
            b = b.toLowerCase().trim();
            const maxLen = Math.max(a.length, b.length);
            if (maxLen === 0) return 1;
            return 1 - levenshtein(a, b) / maxLen;
        }
```

- [ ] **Step 3: Add buildPopupContent() function**

Add this function after `textSimilarity`. It replaces the inline popup HTML in `initializeMarkers()`:

```js
        function buildPopupContent(node) {
            const nodeIndex = routeNodes.indexOf(node);
            const nodeLabel = nodeIndex === 0
                ? '🚀 LÄHTÖ'
                : (nodeIndex === routeNodes.length - 1 ? '🏁 MAALI' : '');
            const inProximity = !REQUIRE_GPS_PROXIMITY || node.inProximity;

            let challengeHtml = '';
            if (node.challenge) {
                if (node.challengeSolved) {
                    challengeHtml = `
                        <div style="border:2px solid #198754;border-radius:8px;overflow:hidden;margin-bottom:8px">
                            <div style="background:#d1e7dd;padding:8px 12px;font-weight:600;font-size:12px;color:#0f5132">
                                ✅ Haaste suoritettu!
                            </div>
                        </div>`;
                } else {
                    const lockBadge = inProximity
                        ? '<span style="background:#d1e7dd;color:#0f5132;padding:2px 8px;border-radius:10px;font-size:11px">📍 Olet paikalla!</span>'
                        : '<span style="background:#e9ecef;color:#495057;padding:2px 8px;border-radius:10px;font-size:11px">🔒 Ole lähempänä</span>';

                    let inputHtml = '';
                    if (node.challenge.type === 'multiple_choice') {
                        const opts = node.challenge.options.map((opt, i) => `
                            <label style="display:flex;align-items:center;gap:8px;margin-bottom:4px;cursor:${inProximity ? 'pointer' : 'not-allowed'}">
                                <input type="radio" name="challenge_${node.id}" value="${i}" ${inProximity ? '' : 'disabled'}>
                                <span style="font-size:13px">${escapeHtml(opt)}</span>
                            </label>`).join('');
                        inputHtml = `
                            <div style="margin-bottom:8px">${opts}</div>
                            <button class="btn btn-sm btn-warning w-100"
                                    onclick="submitChallenge(${node.id})"
                                    ${inProximity ? '' : 'disabled'}>Vastaa</button>`;
                    } else if (node.challenge.type === 'text') {
                        inputHtml = `
                            <div style="margin-bottom:8px">
                                <input type="text" class="form-control form-control-sm"
                                       id="challengeInput_${node.id}"
                                       placeholder="Kirjoita vastauksesi..."
                                       ${inProximity ? '' : 'disabled'}>
                            </div>
                            <button class="btn btn-sm btn-warning w-100"
                                    onclick="submitChallenge(${node.id})"
                                    ${inProximity ? '' : 'disabled'}>Vastaa</button>`;
                    }

                    const borderColor = inProximity ? '#ffc107' : '#dee2e6';
                    const headerBg    = inProximity ? '#fff3cd' : '#f8f9fa';
                    const labelColor  = inProximity ? '#856404' : '#666';
                    challengeHtml = `
                        <div style="border:${inProximity ? '2px' : '1px'} solid ${borderColor};border-radius:8px;overflow:hidden;margin-bottom:8px;opacity:${inProximity ? '1' : '0.8'}">
                            <div style="background:${headerBg};padding:8px 12px;display:flex;align-items:center;gap:6px;border-bottom:1px solid ${borderColor}">
                                <span>⚡</span>
                                <span style="font-weight:600;font-size:12px;color:${labelColor}">Haaste</span>
                                <span style="margin-left:auto">${lockBadge}</span>
                            </div>
                            <div style="padding:10px 12px;background:#fff">
                                <p style="margin:0 0 10px;font-weight:500;font-size:13px">${escapeHtml(node.challenge.question)}</p>
                                ${inputHtml}
                                <div id="challengeError_${node.id}"
                                     style="display:none;color:#dc3545;font-size:12px;margin-top:6px">
                                    ❌ Väärin, yritä uudelleen!
                                </div>
                            </div>
                        </div>`;
                }
            }

            const canCheckIn = inProximity && (!node.challenge || node.challengeSolved);

            return `
                <div class="node-popup">
                    ${nodeLabel ? `<div class="text-center mb-2"><strong>${nodeLabel}</strong></div>` : ''}
                    <h5>${escapeHtml(node.name)}</h5>
                    <div class="mb-2">${node.description || ''}</div>
                    ${challengeHtml}
                    <button class="btn btn-sm btn-primary w-100"
                            onclick="markAsVisited(${node.id})"
                            ${canCheckIn ? '' : 'disabled'}>
                        Merkkaa käydyksi ✓
                    </button>
                </div>`;
        }
```

- [ ] **Step 4: Update initializeMarkers() to use buildPopupContent**

Find `initializeMarkers()`. Replace the inline popup HTML block:
```js
                const nodeLabel = index === 0 ? '🚀 LÄHTÖ' : (index === routeNodes.length - 1 ? '🏁 MAALI' : '');
                const popupContent = `
                    <div class="node-popup">
                        ${nodeLabel ? `<div class="text-center mb-2"><strong>${nodeLabel}</strong></div>` : ''}
                        <h5>${node.name}</h5>
                        <p>${node.description}</p>
                        <button class="btn btn-sm btn-primary" onclick="markAsVisited(${node.id})">
                            Merkkaa käydyksi ✓
                        </button>
                    </div>
                `;

                marker.bindPopup(popupContent);
```
With:
```js
                marker.bindPopup(buildPopupContent(node));
```

- [ ] **Step 5: Add submitChallenge() function**

Add this after `buildPopupContent()`:
```js
        window.submitChallenge = function(nodeId) {
            const node = routeNodes.find(n => n.id === nodeId);
            if (!node || !node.challenge || node.challengeSolved) return;

            let isCorrect = false;

            if (node.challenge.type === 'multiple_choice') {
                const selected = document.querySelector(`input[name="challenge_${nodeId}"]:checked`);
                if (!selected) { alert('Valitse vaihtoehto ensin.'); return; }
                isCorrect = parseInt(selected.value, 10) === node.challenge.correct_index;
            } else if (node.challenge.type === 'text') {
                const input = document.getElementById(`challengeInput_${nodeId}`);
                if (!input || !input.value.trim()) { alert('Kirjoita vastaus ensin.'); return; }
                isCorrect = textSimilarity(input.value.trim(), node.challenge.answer) >= 0.7;
            }

            if (isCorrect) {
                node.challengeSolved = true;
                markers[nodeId].setPopupContent(buildPopupContent(node));
            } else {
                const errorEl = document.getElementById(`challengeError_${nodeId}`);
                if (errorEl) {
                    errorEl.style.display = '';
                    setTimeout(() => { errorEl.style.display = 'none'; }, 3000);
                }
            }
        };
```

- [ ] **Step 6: Refactor checkProximity() to track per-node proximity**

Replace the entire `checkProximity()` function:

```js
        function checkProximity(userLat, userLng) {
            let nearestNode = null;
            let nearestDistance = Infinity;
            let closestUnvisited = null;
            let closestDistance = Infinity;

            routeNodes.forEach(node => {
                if (!node.visited) {
                    const distance = calculateDistance(userLat, userLng, node.lat, node.lng);
                    const wasInProximity = node.inProximity;
                    node.inProximity = distance < PROXIMITY_THRESHOLD;

                    // Rebuild popup if proximity state changed
                    if (wasInProximity !== node.inProximity) {
                        markers[node.id].setPopupContent(buildPopupContent(node));
                    }

                    if (distance < PROXIMITY_THRESHOLD && distance < nearestDistance) {
                        nearestNode = node;
                        nearestDistance = distance;
                    }
                    if (distance < closestDistance) {
                        closestUnvisited = node;
                        closestDistance = distance;
                    }
                }
            });

            if (nearestNode) {
                $('#distance-info').html(`
                    <div class="alert alert-success mb-0 py-2">
                        <strong>📍 Nearby!</strong><br>
                        <small>${nearestNode.name}<br>${Math.round(nearestDistance)}m päässä</small>
                    </div>
                `);
                markers[nearestNode.id].openPopup();
            } else if (closestUnvisited) {
                $('#distance-info').html(`
                    <div class="alert alert-info mb-0 py-2">
                        <strong>Seuraava:</strong><br>
                        <small>${closestUnvisited.name}<br>${Math.round(closestDistance)}m päässä</small>
                    </div>
                `);
            }
        }
```

- [ ] **Step 7: Update markAsVisited() with proximity and challenge checks**

Find `window.markAsVisited` and replace the entire function with:

```js
        window.markAsVisited = function(nodeId) {
            const node = routeNodes.find(n => n.id === nodeId);
            if (!node || node.visited) return;
            if (REQUIRE_GPS_PROXIMITY && !node.inProximity) return;
            if (node.challenge && !node.challengeSolved) return;

            node.visited = true;
            trackVisit(nodeId);
            markers[nodeId].setIcon(visitedIcon);
            updateProgress();

            markers[nodeId].closePopup();

            const acornDiv = document.createElement('div');
            acornDiv.className = 'acorn-celebration';
            acornDiv.innerHTML = '<img src="../images/acorn.png" alt="Acorn">';
            document.body.appendChild(acornDiv);

            setTimeout(() => { acornDiv.remove(); }, 2000);

            setTimeout(() => {
                const celebrationPopup = `
                    <div class="node-popup text-center">
                        <h5>🎉 Hienoa!</h5>
                        <p>Olet löytänyt rastin: <strong>${node.name}</strong></p>
                    </div>
                `;
                markers[nodeId].bindPopup(celebrationPopup).openPopup();
            }, 2100);
        };
```

- [ ] **Step 8: Verify end-to-end**

1. Set `define('REQUIRE_GPS_PROXIMITY', false)` in `config/constants.php` temporarily.
2. Create a route with one **multiple-choice** node and one **text** node via `new-route.php`.
3. Open `http://localhost/HavuGamification/pages/game.php?route=<route_uuid>`.
4. Click the multiple-choice node marker — popup should show the challenge.
5. Select the WRONG option and click "Vastaa" — red "Väärin" message should appear and disappear after 3s.
6. Select the CORRECT option and click "Vastaa" — challenge panel collapses to green "✅ Haaste suoritettu!", check-in button becomes enabled.
7. Click "Merkkaa käydyksi ✓" — node is marked, acorn animation plays.
8. Click the text node marker — type a misspelled version of the answer (e.g., if answer is "Mänty", type "Mänt") — should pass (≥70% similarity). Type something completely wrong — should fail.
9. Re-enable GPS: set `REQUIRE_GPS_PROXIMITY` back to `true`. Open the game page — check-in button and challenge inputs should be disabled. Confirm the popup shows "🔒 Ole lähempänä".

- [ ] **Step 9: Commit**

```bash
git add pages/game.php
git commit -m "feat: implement node challenge gameplay in game page"
```

---

## Task 10: Final Verification and Cleanup

- [ ] **Step 1: Set REQUIRE_GPS_PROXIMITY to true for production**

Confirm `config/constants.php` has:
```php
define('REQUIRE_GPS_PROXIMITY', true);
```

- [ ] **Step 2: Full flow test**

1. Admin creates a route with a mix of: nodes with no challenge, MC challenge, and text challenge.
2. Admin edits that route — all challenges pre-populate correctly.
3. Admin saves edits — changes persist (check phpMyAdmin).
4. Player opens game page — all challenges visible, all check-in buttons disabled.
5. Simulate proximity by temporarily setting `REQUIRE_GPS_PROXIMITY = false` and re-run the verify steps from Task 9 Step 8.

- [ ] **Step 3: Final commit**

```bash
git add -A
git status  # review — make sure nothing unexpected is staged
git commit -m "feat: node challenges feature complete"
```
