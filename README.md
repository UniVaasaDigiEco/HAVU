# HAVU-trailgame

HAVU-trailgame is a geocaching-style web app for building and playing interactive outdoor routes. Creators define GPS checkpoints, rich content, route-specific proximity settings, and optional challenges; players follow the route on a map and check in when they are close enough.

The app is localized in Finnish, English, and Swedish. The localized product names are HAVU-polkupeli in Finnish and HAVU-stigspelet in Swedish.

## What It Does

### Player Experience

- GPS-based gameplay in the browser with Leaflet maps and browser geolocation.
- Route-specific proximity handling with a global fallback threshold.
- Checkpoint content with formatted text, embedded media, and optional multiple-choice or text challenges.
- Player dashboard with in-progress routes, completed routes, and account settings.
- Player-to-creator messaging from route pages and the player dashboard.
- Language switching across Finnish, English, and Swedish.

### Creator Experience

- Route creation, editing, copying, publishing, deleting, and testing from the admin area.
- Map-based checkpoint editor with drag-and-drop positioning, route ordering, and location search.
- Summernote-based rich text editing for checkpoint content.
- Route statistics for creators.
- Creator route collection page for sharing all routes from one public link.

### Site Operations

- Feedback widget for contact requests and bug reports.
- Internal system-admin panel for user management, bulk messaging, and feedback inbox handling.

## Tech Stack

- Backend: PHP 8.4, MySQLi, Composer with `ramsey/uuid`
- Frontend: Bootstrap 5, Bootstrap Icons, jQuery, Leaflet.js, Summernote
- Security and integrations: Google reCAPTCHA v3, CSP/security headers on gameplay pages
- Styling: SCSS compiled to `css/bs-custom.css`
- Runtime: XAMPP or Laragon

## Setup

### Requirements

- PHP 8.4 with MySQL/MariaDB
- Composer
- Node.js and npm for SCSS compilation

### Installation

1. Copy `.env.example` to `.env` and set your database, upload, and reCAPTCHA values.
2. Import the schema from `_SQL/havu-sql-structure.sql`.
3. Import `_SQL/add_messages_table.sql` so the messaging and feedback inbox features work.
4. Install PHP dependencies with `composer install`.
5. Install frontend dependencies with `npm install`.
6. Compile the SCSS once with `npm run scss-compile`.
7. Open the app in your browser, for example at `http://localhost/HavuGamification/`.

### Useful Commands

```bash
composer install
npm install
npm run scss-compile
npm run scss-watch
npx eslint .
```

There is no automated test suite in this repository. `npm test` is a placeholder and always fails.

## Operational Notes

- Most pages and actions initialize the session with `Security::initSession()`.
- Database access should go through `Tools::getDb()` and the domain classes in `classes/`.
- Use `public_id` UUIDs at page and API boundaries, and numeric IDs for relation tables such as `node_route_cross`, `node_visits`, and `route_completions`.
- Route editing replaces the full checkpoint set instead of patching node order incrementally.
- Uploaded media is stored under `uploads/{user_public_id}/{uuid}.{ext}`.
- `REQUIRE_GPS_PROXIMITY` and `PROXIMITY_THRESHOLD` in `config/constants.php` control gameplay distance checks.
- `MAINTENANCE_MODE` in `.env` can replace the normal landing page with a maintenance notice.

## Terms of Service

The app requires users to accept terms of service when registering. Existing users who have not yet accepted are shown a blocking modal on their next login.

The `tos/` directory contains the TOS PDFs served to users, one per locale (`tos_fi.pdf`, `tos_en.pdf`, `tos_sv.pdf`). The included PDFs are specific to the HAVU project and the University of Vaasa — **if you deploy your own instance, you must replace them with your own terms.**

Templates are provided for both documents:

- Terms of service: [tos/tos_template.md](tos/tos_template.md)
- Privacy notice: [privacy/privacy_notice_template.md](privacy/privacy_notice_template.md)

To use them:

1. Fill in the placeholders (organization name, contact details, jurisdiction, etc.).
2. Translate or adapt as needed for your supported locales.
3. Export the TOS to PDF and place the files in `tos/` as `tos_fi.pdf`, `tos_en.pdf`, and `tos_sv.pdf`.
4. Export the privacy notice to PDF and place the files in `privacy/` as `privacy_notice_fi.pdf`, `privacy_notice_en.pdf`, and `privacy_notice_sv.pdf`.

## Documentation

- Product and setup overview: this README
- End-user and admin manuals (Finnish only): [pages/files/](pages/files/)
- Terms of service template: [tos/tos_template.md](tos/tos_template.md)
- Privacy notice template: [privacy/privacy_notice_template.md](privacy/privacy_notice_template.md)

## Authors

Jyri Nieminen, Vaiva Stanisauskaite and Heli Siirilä

## License

ISC
