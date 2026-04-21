# Feedback / Bug Reporting Widget — Design Spec

**Date:** 2026-04-21
**Status:** Approved

---

## Overview

A modal feedback widget available across all dashboard and player-facing pages, and integrated into the game page status bar. Users fill in a typed message (contact, bug report, or feature suggestion); the server saves the submission to the database and sends an email to the support address.

---

## Database

New table: `feedback_submissions`

| Column | Type | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | |
| `type` | ENUM(`contact`, `bug`, `feature`) | maps to the dropdown selection |
| `name` | VARCHAR(100) NOT NULL | |
| `email` | VARCHAR(255) NOT NULL | |
| `message` | TEXT NOT NULL | |
| `user_id` | INT NULL (FK → `users.id`) | NULL if not logged in |
| `page_url` | VARCHAR(500) NOT NULL | captured server-side via `$_SERVER['REQUEST_URI']` |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |

Migration SQL goes in `_SQL/`.

---

## Backend — `actions/submit-feedback.php`

Accepts POST, returns JSON (`{"ok": true}` or `{"ok": false, "error": "..."}`).

**Flow:**
1. Validate required fields: `type` (must be one of `contact`, `bug`, `feature`), `name`, `email` (valid format), `message` (non-empty). Return error JSON on failure.
2. Verify reCAPTCHA v3 token via cURL POST to `https://www.google.com/recaptcha/api/siteverify`. Reject if score < 0.5.
3. Insert row into `feedback_submissions` (include `user_id` from session if set).
4. Send email via `mail()` to `support@havupeli.jansoftworks.fi` (see Email Format below).
5. Return `{"ok": true}`.

All DB operations use prepared statements. No redirect — JSON response only.

---

## Frontend

### New files

**`includes/_feedback_widget.php`**
- Renders the Bootstrap modal HTML
- Pre-fills `name` and `email` inputs with PHP session data if the user is logged in
- Contains a hidden input `page_url` populated with `<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>`
- Contains the floating trigger button (hidden on game page — see Placement)

**`js/feedback-widget.js`**
- Opens/closes the modal
- On submit: calls `grecaptcha.execute(RECAPTCHA_SITE_KEY, {action: 'feedback'})`, appends the token to FormData, POSTs to `actions/submit-feedback.php` via `fetch()`
- On success: shows inline Finnish success message inside the modal, resets the form
- On error: shows inline Finnish error message

### Modal fields (Finnish UI)

| Field | Type | Notes |
|---|---|---|
| Tyyppi | `<select>` | Ota yhteyttä / Ilmoita virheestä / Ehdota ominaisuutta |
| Nimi | `<input text>` | Pre-filled if logged in |
| Sähköposti | `<input email>` | Pre-filled if logged in |
| Viesti | `<textarea>` | |
| page_url | `<input hidden>` | Server-side value |
| recaptcha_token | `<input hidden>` | Populated by JS before submit |

reCAPTCHA v3 badge shown per Google's terms of service.

### Placement

| Location | Trigger |
|---|---|
| `pages/admin/dashboard.php` | Persistent floating button (bottom-right) |
| `pages/admin/new-route.php` | Persistent floating button |
| `pages/admin/edit-route.php` | Persistent floating button |
| `pages/admin/delete-route.php` | Persistent floating button |
| `pages/player/dashboard.php` | Persistent floating button |
| `pages/routes.php` | Persistent floating button |
| `pages/game.php` | Small "Palaute" link inside the existing top-right status panel; no floating button |

Each page includes `_feedback_widget.php` and loads the reCAPTCHA v3 script + `feedback-widget.js`.

---

## Configuration

Two new keys in `.env` and `.env.example`:

```
RECAPTCHA_SITE_KEY   = ''
RECAPTCHA_SECRET_KEY = ''
```

Exposed as PHP constants `RECAPTCHA_SITE_KEY` and `RECAPTCHA_SECRET_KEY` in `config/constants.php`, following the existing DB constant pattern.

---

## Email Format

Plain text, English, sent via `mail()`.

```
Subject: HAVU: New [Type] submission

Hi,

A new message was submitted via the HAVU feedback form.

Type:       Bug Report
Name:       Matti Meikäläinen
Email:      matti@example.com
Page:       /HavuGamification/pages/game.php
Time:       21.04.2026 14:32
Logged in:  Yes (user_id: 42)

Message:
-----------
[user message]
-----------

---
HAVU Platform
```

`From` header: `noreply@havupeli.jansoftworks.fi`
`Reply-To` header: submitter's email address

Type label mapping: `contact` → "Contact Request", `bug` → "Bug Report", `feature` → "Feature Suggestion"

---

## Out of Scope

- Admin UI for browsing submissions (future work — data is in DB ready for it)
- Rate limiting beyond reCAPTCHA v3 scoring
- File attachments
