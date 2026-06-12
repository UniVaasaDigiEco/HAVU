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

## Documentation

- Product and setup overview: this README
- End-user and admin manuals: [_DOCS/_MANUALS](_DOCS/_MANUALS)
- GPS threshold behavior: [_DOCS/GPS_Threshold_Testing.md](_DOCS/GPS_Threshold_Testing.md)
- Message widget integration checklist: [_DOCS/message_widget_integration_checklist.md](_DOCS/message_widget_integration_checklist.md)

## Authors

Jyri Nieminen, Vaiva Stanisauskaite and Heli Siirilä

## License

ISC
