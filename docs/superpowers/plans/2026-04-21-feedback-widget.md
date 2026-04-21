# Feedback Widget Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a reusable feedback/bug-reporting modal widget to all dashboard and game pages, backed by a `feedback_submissions` DB table and email notification to `support@havupeli.jansoftworks.fi`.

**Architecture:** A PHP include (`includes/_feedback_widget.php`) renders a Bootstrap modal with server-side pre-filled fields; `js/feedback-widget.js` handles open/close and AJAX submission via `fetch()`; `actions/submit-feedback.php` validates input, verifies reCAPTCHA v3 via cURL, inserts into the DB, and sends a plain-text email.

**Tech Stack:** PHP 8.4, MySQL (MySQLi), Bootstrap 5, vanilla JS (fetch API), Google reCAPTCHA v3

---

## File Map

| Status | Path | Responsibility |
|---|---|---|
| Create | `_SQL/add_feedback_submissions.sql` | DB migration |
| Create | `includes/_feedback_widget.php` | Modal HTML + optional floating button |
| Create | `js/feedback-widget.js` | Modal open/close + AJAX submit + reCAPTCHA |
| Create | `actions/submit-feedback.php` | Validate, verify CAPTCHA, DB insert, email |
| Modify | `.env.example` | Add reCAPTCHA key placeholders |
| Modify | `config/constants.php` | Expose reCAPTCHA keys as PHP constants |
| Modify | `pages/admin/dashboard.php` | Include widget + reCAPTCHA script |
| Modify | `pages/admin/new-route.php` | Include widget + reCAPTCHA script |
| Modify | `pages/admin/edit-route.php` | Include widget + reCAPTCHA script |
| Modify | `pages/admin/delete-route.php` | Include widget + reCAPTCHA script |
| Modify | `pages/player/dashboard.php` | Include widget + reCAPTCHA script |
| Modify | `pages/routes.php` | Include widget + reCAPTCHA script |
| Modify | `pages/game.php` | CSP update + reCAPTCHA script + modal include + feedback link in info panel |
| Modify | `css/scss/bs-custom.scss` | Floating button CSS |

---

### Task 1: Database Migration

**Files:**
- Create: `_SQL/add_feedback_submissions.sql`

- [ ] **Step 1: Create migration file**

Create `_SQL/add_feedback_submissions.sql` with this content:

```sql
CREATE TABLE `feedback_submissions` (
  `id`         INT            NOT NULL AUTO_INCREMENT,
  `type`       ENUM('contact','bug','feature') NOT NULL,
  `name`       VARCHAR(100)   NOT NULL,
  `email`      VARCHAR(255)   NOT NULL,
  `message`    TEXT           NOT NULL,
  `user_id`    INT            NULL,
  `page_url`   VARCHAR(500)   NOT NULL,
  `created_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type`       (`type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_feedback_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Run the migration**

Open phpMyAdmin → select database `jansoftw_havu` → SQL tab → paste and execute.

Or via MySQL CLI:
```bash
mysql -u root jansoftw_havu < _SQL/add_feedback_submissions.sql
```

Expected: `Query OK, 0 rows affected`. Table `feedback_submissions` appears in the table list.

- [ ] **Step 3: Commit**

```bash
git add _SQL/add_feedback_submissions.sql
git commit -m "feat: add feedback_submissions table migration"
```

---

### Task 2: Configuration

**Files:**
- Modify: `.env.example`
- Modify: `config/constants.php`

- [ ] **Step 1: Update `.env.example`**

The file currently returns an array. Add the two reCAPTCHA entries so the full file reads:

```php
<?php
/**
 * Environment Configuration Template
 *
 * Copy this file to .env.php and fill in your actual credentials.
 * DO NOT commit .env.php to version control.
 */

return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'your_database_name',
    'DB_USER' => 'your_database_user',
    'DB_PASS' => 'your_database_password',
    'UPLOAD_MAX_IMAGE_MB' => 10,
    'UPLOAD_MAX_VIDEO_MB' => 100,
    'RECAPTCHA_SITE_KEY'   => '',
    'RECAPTCHA_SECRET_KEY' => '',
];
```

- [ ] **Step 2: Add keys to your local `.env`**

Open `.env` (not committed) and add the same two keys with real values obtained from https://www.google.com/recaptcha/admin (create a reCAPTCHA v3 site for `localhost`):

```php
'RECAPTCHA_SITE_KEY'   => 'YOUR_V3_SITE_KEY',
'RECAPTCHA_SECRET_KEY' => 'YOUR_V3_SECRET_KEY',
```

- [ ] **Step 3: Add constants to `config/constants.php`**

`config/constants.php` currently ends with these two lines (around line 41–42):

```php
define('UPLOAD_MAX_IMAGE_BYTES', ($env['UPLOAD_MAX_IMAGE_MB'] ?? 10) * 1024 * 1024);
define('UPLOAD_MAX_VIDEO_BYTES', ($env['UPLOAD_MAX_VIDEO_MB'] ?? 100) * 1024 * 1024);
```

Add two more lines directly after them:

```php
define('RECAPTCHA_SITE_KEY',   $env['RECAPTCHA_SITE_KEY']   ?? '');
define('RECAPTCHA_SECRET_KEY', $env['RECAPTCHA_SECRET_KEY'] ?? '');
```

- [ ] **Step 4: Commit**

```bash
git add .env.example config/constants.php
git commit -m "feat: add reCAPTCHA config constants"
```

---

### Task 3: Submit Action

**Files:**
- Create: `actions/submit-feedback.php`

- [ ] **Step 1: Create `actions/submit-feedback.php`**

```php
<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');

Security::initSession();

header('Content-Type: application/json');

function jsonError(string $msg): never {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Invalid request method.');
}

$type     = trim($_POST['type']            ?? '');
$name     = trim($_POST['name']            ?? '');
$email    = trim($_POST['email']           ?? '');
$message  = trim($_POST['message']         ?? '');
$page_url = trim($_POST['page_url']        ?? '');
$token    = trim($_POST['recaptcha_token'] ?? '');

if (!in_array($type, ['contact', 'bug', 'feature'], true)) {
    jsonError('Virheellinen viestityyppi.');
}
if ($name === '' || mb_strlen($name) > 100) {
    jsonError('Nimi on pakollinen (max 100 merkkiä).');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Anna kelvollinen sähköpostiosoite.');
}
if ($message === '') {
    jsonError('Viesti on pakollinen.');
}
if ($token === '') {
    jsonError('reCAPTCHA-tarkistus epäonnistui.');
}

// Verify reCAPTCHA v3 via cURL
$ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'secret'   => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'],
    ]),
]);
$raw = curl_exec($ch);
curl_close($ch);

$rc = json_decode($raw, true);
if (!$rc || !($rc['success'] ?? false) || ($rc['score'] ?? 0) < 0.5) {
    jsonError('reCAPTCHA-tarkistus epäonnistui. Yritä uudelleen.');
}

// Resolve internal user_id from session if logged in
$user_id = null;
if (!empty($_SESSION['user_public_id'])) {
    try {
        $user_id = Tools::getUserIdByPublicId($_SESSION['user_public_id']);
    } catch (Exception $e) {
        // Non-fatal — submit anonymously
    }
}

// Save to DB
$db   = Tools::getDb();
$stmt = $db->prepare(
    "INSERT INTO feedback_submissions (type, name, email, message, user_id, page_url)
     VALUES (?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    jsonError('Palautteen tallentaminen epäonnistui.');
}
$stmt->bind_param('ssssis', $type, $name, $email, $message, $user_id, $page_url);
if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    jsonError('Palautteen tallentaminen epäonnistui.');
}
$stmt->close();
$db->close();

// Send email (plain text — do NOT htmlspecialchars the body)
$type_labels = ['contact' => 'Contact Request', 'bug' => 'Bug Report', 'feature' => 'Feature Suggestion'];
$type_label  = $type_labels[$type];
$time        = (new DateTime())->format('d.m.Y H:i');
$logged_str  = $user_id !== null ? "Yes (user_id: {$user_id})" : 'No';

$body = "Hi,\n\n"
      . "A new message was submitted via the HAVU feedback form.\n\n"
      . "Type:       {$type_label}\n"
      . "Name:       {$name}\n"
      . "Email:      {$email}\n"
      . "Page:       {$page_url}\n"
      . "Time:       {$time}\n"
      . "Logged in:  {$logged_str}\n\n"
      . "Message:\n"
      . "-----------\n"
      . "{$message}\n"
      . "-----------\n\n"
      . "---\nHAVU Platform";

// Strip newlines from Reply-To to prevent header injection
$safe_reply_to = str_replace(["\r", "\n"], '', $email);

$headers = "From: noreply@havupeli.jansoftworks.fi\r\n"
         . "Reply-To: {$safe_reply_to}\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n";

mail('support@havupeli.jansoftworks.fi', "HAVU: New {$type_label} submission", $body, $headers);

echo json_encode(['ok' => true]);
```

- [ ] **Step 2: Commit**

```bash
git add actions/submit-feedback.php
git commit -m "feat: add feedback submit action with reCAPTCHA and DB persistence"
```

---

### Task 4: Widget Include

**Files:**
- Create: `includes/_feedback_widget.php`
- Modify: `css/scss/bs-custom.scss`

- [ ] **Step 1: Create `includes/_feedback_widget.php`**

```php
<?php
// Fetch logged-in user's name/email for pre-filling.
// Tools and Security are already required by the including page.
$_fw_name  = '';
$_fw_email = '';
if (!empty($_SESSION['user_public_id'])) {
    try {
        $_fw_user  = Tools::getUserWithPublicId($_SESSION['user_public_id']);
        $_fw_name  = $_fw_user->getFullName();
        $_fw_email = $_fw_user->getEmail();
        unset($_fw_user);
    } catch (Exception $e) {
        // Non-fatal — leave fields empty
    }
}
?>

<?php if (empty($feedback_widget_no_float)): ?>
<button type="button"
        class="btn btn-primary shadow feedback-float-btn"
        id="feedback-float-btn"
        data-bs-toggle="modal"
        data-bs-target="#feedbackModal"
        title="Lähetä palaute">
    <i class="bi bi-chat-dots-fill me-1"></i>Palaute
</button>
<?php endif; ?>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1"
     aria-labelledby="feedbackModalLabel" aria-hidden="true"
     data-action="<?= htmlspecialchars(ROOT_DIR . 'actions/submit-feedback.php', ENT_QUOTES, 'UTF-8') ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel">
                    <i class="bi bi-chat-dots-fill me-2"></i>Palaute &amp; yhteydenotto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Sulje"></button>
            </div>
            <div class="modal-body">
                <div id="feedback-alert" class="alert d-none" role="alert"></div>
                <form id="feedback-form" novalidate>
                    <input type="hidden" name="page_url"
                           value="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="recaptcha_token" id="recaptcha-token">

                    <div class="mb-3">
                        <label for="feedback-type" class="form-label fw-semibold">
                            Tyyppi <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="feedback-type" name="type" required>
                            <option value="" disabled selected>Valitse...</option>
                            <option value="contact">Ota yhteyttä</option>
                            <option value="bug">Ilmoita virheestä</option>
                            <option value="feature">Ehdota ominaisuutta</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="feedback-name" class="form-label fw-semibold">
                            Nimi <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="feedback-name" name="name"
                               value="<?= htmlspecialchars($_fw_name, ENT_QUOTES, 'UTF-8') ?>"
                               maxlength="100" required>
                    </div>

                    <div class="mb-3">
                        <label for="feedback-email" class="form-label fw-semibold">
                            Sähköposti <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control" id="feedback-email" name="email"
                               value="<?= htmlspecialchars($_fw_email, ENT_QUOTES, 'UTF-8') ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="feedback-message" class="form-label fw-semibold">
                            Viesti <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="feedback-message" name="message"
                                  rows="4" required></textarea>
                    </div>

                    <p class="text-muted small mb-3">
                        Tämä lomake on suojattu reCAPTCHA:lla.
                        <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Tietosuoja</a>
                        ja
                        <a href="https://policies.google.com/terms" target="_blank" rel="noopener">käyttöehdot</a>.
                    </p>

                    <button type="submit" class="btn btn-primary w-100" id="feedback-submit">
                        <span id="feedback-submit-text">
                            <i class="bi bi-send me-1"></i>Lähetä
                        </span>
                        <span id="feedback-submit-spinner" class="d-none">
                            <span class="spinner-border spinner-border-sm me-1" role="status"
                                  aria-hidden="true"></span>Lähetetään…
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>const RECAPTCHA_SITE_KEY = <?= json_encode(RECAPTCHA_SITE_KEY) ?>;</script>
<script src="<?= ROOT_DIR ?>js/feedback-widget.js"></script>
```

- [ ] **Step 2: Add floating button CSS to `css/scss/bs-custom.scss`**

Open `css/scss/bs-custom.scss`. Add the following block before the final line (or at the very end of any custom section you find appropriate):

```scss
// Feedback floating button
.feedback-float-btn {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 1050;
    border-radius: 50px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}
```

- [ ] **Step 3: Recompile SCSS**

```bash
npm run scss-compile
```

Expected: exits with no errors; `css/bs-custom.css` is updated.

- [ ] **Step 4: Commit**

```bash
git add includes/_feedback_widget.php css/scss/bs-custom.scss css/bs-custom.css
git commit -m "feat: add feedback widget include and float button styles"
```

---

### Task 5: Widget JavaScript

**Files:**
- Create: `js/feedback-widget.js`

- [ ] **Step 1: Create `js/feedback-widget.js`**

```javascript
(function () {
    'use strict';

    const modal   = document.getElementById('feedbackModal');
    const form    = document.getElementById('feedback-form');
    const alertEl = document.getElementById('feedback-alert');
    const spinner = document.getElementById('feedback-submit-spinner');
    const btnText = document.getElementById('feedback-submit-text');
    const submitBtn = document.getElementById('feedback-submit');

    if (!form) return;

    const endpoint = modal.dataset.action;

    function showAlert(message, isError) {
        alertEl.textContent = message;
        alertEl.className = 'alert ' + (isError ? 'alert-danger' : 'alert-success');
        alertEl.classList.remove('d-none');
    }

    function setLoading(loading) {
        spinner.classList.toggle('d-none', !loading);
        btnText.classList.toggle('d-none', loading);
        submitBtn.disabled = loading;
    }

    modal.addEventListener('hidden.bs.modal', function () {
        alertEl.classList.add('d-none');
        setLoading(false);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        setLoading(true);

        grecaptcha.ready(function () {
            grecaptcha.execute(RECAPTCHA_SITE_KEY, { action: 'feedback' })
                .then(function (token) {
                    document.getElementById('recaptcha-token').value = token;

                    fetch(endpoint, { method: 'POST', body: new FormData(form) })
                        .then(function (res) { return res.json(); })
                        .then(function (json) {
                            setLoading(false);
                            if (json.ok) {
                                showAlert('Kiitos! Viestisi on lähetetty.', false);
                                form.reset();
                                form.classList.remove('was-validated');
                            } else {
                                showAlert(json.error || 'Jokin meni pieleen. Yritä uudelleen.', true);
                            }
                        })
                        .catch(function () {
                            setLoading(false);
                            showAlert('Verkkovirhe. Tarkista yhteytesi ja yritä uudelleen.', true);
                        });
                });
        });
    });

    // Exposed for the game page inline trigger
    window.openFeedbackModal = function () {
        bootstrap.Modal.getOrCreateInstance(modal).show();
    };
}());
```

- [ ] **Step 2: Commit**

```bash
git add js/feedback-widget.js
git commit -m "feat: add feedback widget JS"
```

---

### Task 6: Wire Up Dashboard Pages

**Files:**
- Modify: `pages/admin/dashboard.php`
- Modify: `pages/admin/new-route.php`
- Modify: `pages/admin/edit-route.php`
- Modify: `pages/admin/delete-route.php`
- Modify: `pages/player/dashboard.php`
- Modify: `pages/routes.php`

Note: All these pages already `require_once` `tools.class.php`, which itself requires `config/constants.php`, so `RECAPTCHA_SITE_KEY` is always available when the widget is included.

- [ ] **Step 1: Update `pages/admin/dashboard.php`**

In `<head>`, add before `</head>`:
```html
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
```

Before `</body>` (the final two lines of the file are `</body>` and `</html>`):
```php
<?php require_once '../../includes/_feedback_widget.php'; ?>
</body>
</html>
```

- [ ] **Step 2: Update `pages/admin/new-route.php`**

In `<head>`, add before `</head>`:
```html
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
```

Before `</body>` (the file ends with `</script>\n</body>\n</html>`):
```php
<?php require_once '../../includes/_feedback_widget.php'; ?>
</body>
</html>
```

- [ ] **Step 3: Update `pages/admin/edit-route.php`**

In `<head>`, add before `</head>`:
```html
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
```

Before `</body>` (the file ends with `</script>\n</body>\n</html>`):
```php
<?php require_once '../../includes/_feedback_widget.php'; ?>
</body>
</html>
```

- [ ] **Step 4: Update `pages/admin/delete-route.php`**

In `<head>`, add before `</head>`:
```html
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
```

Before `</body>` (the file ends with `</div>\n</div>\n</div>\n</div>\n</body>\n</html>`):
```php
<?php require_once '../../includes/_feedback_widget.php'; ?>
</body>
</html>
```

- [ ] **Step 5: Update `pages/player/dashboard.php`**

In `<head>`, add before `</head>` (currently at line 125):
```html
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
```

Before `</body>` (the file ends with `</div>\n</body>\n</html>`):
```php
<?php require_once '../../includes/_feedback_widget.php'; ?>
</body>
</html>
```

- [ ] **Step 6: Update `pages/routes.php`**

In `<head>`, add before `</head>` (currently at line 84):
```html
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
```

Before `</body>` (the file ends with `</div>\n</body>\n</html>`):
```php
<?php require_once '../includes/_feedback_widget.php'; ?>
</body>
</html>
```

- [ ] **Step 7: Commit**

```bash
git add pages/admin/dashboard.php pages/admin/new-route.php pages/admin/edit-route.php pages/admin/delete-route.php pages/player/dashboard.php pages/routes.php
git commit -m "feat: wire up feedback widget to dashboard and player pages"
```

---

### Task 7: Game Page Integration

**Files:**
- Modify: `pages/game.php`

The game page sets explicit `Content-Security-Policy` headers. reCAPTCHA v3 loads scripts from `https://www.google.com` and `https://www.gstatic.com`, and uses styles from `https://www.gstatic.com` — all currently blocked. These domains must be added to the CSP.

The floating button is suppressed on this page (`$feedback_widget_no_float = true`). Instead, a small "Palaute" link is added at the bottom of the `.info-panel` div, which slides in from the top-right when the player taps the route title button.

- [ ] **Step 1: Update the Content-Security-Policy header**

Find this line (around line 33 in `pages/game.php`):
```php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self' https:; frame-src https://www.youtube.com https://www.youtube-nocookie.com;");
```

Replace with:
```php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://www.gstatic.com; img-src 'self' data: https:; connect-src 'self' https:; frame-src https://www.youtube.com https://www.youtube-nocookie.com;");
```

- [ ] **Step 2: Add reCAPTCHA script to `<head>`**

In `<head>`, add after the existing `<script>` tags (the last script tag in `<head>` loads Leaflet). Add before `</head>`:
```html
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
```

- [ ] **Step 3: Add "Palaute" link inside `.info-panel`**

Find this block inside the `.info-panel` div (around line 653–654):
```html
        <div id="distance-info"></div>
    </div>
```

Replace with:
```html
        <div id="distance-info"></div>
        <div class="mt-2 pt-2 border-top">
            <a href="#" class="small text-muted text-decoration-none"
               onclick="openFeedbackModal(); return false;">
                <i class="bi bi-chat-dots me-1"></i>Palaute
            </a>
        </div>
    </div>
```

- [ ] **Step 4: Include the widget (modal only, no float button) before `</body>`**

The file ends with:
```html
</body>
</html>
```

Replace with:
```php
<?php
$feedback_widget_no_float = true;
require_once '../includes/_feedback_widget.php';
?>
</body>
</html>
```

- [ ] **Step 5: Commit**

```bash
git add pages/game.php
git commit -m "feat: integrate feedback widget into game page"
```

---

### Task 8: Manual Testing Checklist

- [ ] **Step 1: Admin dashboard floating button**

Navigate to `http://localhost/HavuGamification/pages/admin/dashboard.php` (logged in as admin).
- A green "Palaute" button is visible in the bottom-right corner.
- Clicking it opens a Bootstrap modal.
- Name and email fields are pre-filled with the logged-in admin's data.
- Selecting a type, filling in a message, and submitting shows "Kiitos!" without a page reload.
- Check phpMyAdmin: a row appears in `feedback_submissions` with the correct `user_id`.

- [ ] **Step 2: Validation**

Open the modal and click "Lähetä" without filling anything in.
- Bootstrap `was-validated` styling highlights the required fields in red.

Submit with a clearly invalid email (e.g. `notanemail`).
- Server returns an error and the modal shows it inline.

- [ ] **Step 3: Player dashboard**

Navigate to `http://localhost/HavuGamification/pages/player/dashboard.php` (logged in as player).
- Floating "Palaute" button appears. Same behavior as Step 1.

- [ ] **Step 4: Game page**

Navigate to `http://localhost/HavuGamification/pages/game.php?route=<any-uuid>`.
- No floating button is visible.
- Tap/click the route title button (top-right, `info-panel-toggle`). The info panel slides down.
- A small "Palaute" link is visible at the bottom of the panel.
- Clicking it opens the feedback modal.
- Submit a test message; it saves to the DB.

- [ ] **Step 5: Logged-out user**

Log out. Navigate to `pages/routes.php`.
- Floating "Palaute" button appears.
- Name and email fields are empty (not pre-filled).
- Submit works; `user_id` is NULL in the DB row.
