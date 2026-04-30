# Copilot Instructions

## Build, test, and lint commands

| Task | Command | Notes |
| --- | --- | --- |
| Install PHP dependencies | `composer install` | Required for `ramsey/uuid` and autoloading. |
| Install frontend dependencies | `npm install` | `node_modules/` is already committed in this repo, but this is the install command for fresh environments. |
| Compile SCSS once | `npm run scss-compile` | Compiles `css/scss/` into `css/bs-custom.css`. |
| Watch SCSS during development | `npm run scss-watch` | Rebuilds styles on change. |

There is **no lint suite** configured in `package.json` or `composer.json`.

There is **no automated test suite** configured in this repo. `npm test` is only the default placeholder script and exits with an error, so there is no supported full-test or single-test command to run.

## High-level architecture

HAVU is a server-rendered PHP app running directly under XAMPP, with Leaflet/Summernote-heavy pages for the route editor and the game itself. There is no frontend bundler or deploy pipeline: PHP pages render HTML directly, load assets from `node_modules/`, and submit to `actions/*.php` endpoints.

The main server-side pattern is:

1. A page starts the session with `Security::initSession()`.
2. It loads data through `Tools` helpers or by instantiating `User`, `Route`, or `Node`, which fetch their own database state in their constructors.
3. For interactive pages, PHP serializes model data into JavaScript using methods like `Route::toJavaScript()`.
4. POST actions perform writes with prepared statements, then usually redirect back with flash messages in `$_SESSION['flash_messages']`.

The main product flows span multiple files:

### Route authoring flow

- `pages/admin/new-route.php` and `pages/admin/edit-route.php` are rich client-side editors built with Leaflet, Summernote, and `js/challenge-panel.js`.
- The editor keeps route nodes in a browser-side `nodes` array and submits them as JSON through the hidden `nodes_data` field.
- `actions/create_route.php` writes the route, nodes, and `node_route_cross` ordering rows in a transaction.
- `actions/update-route.php` updates route metadata, clears and rebuilds the route's node links from `nodes_data`, and then deletes orphaned old nodes.

### Gameplay and progress flow

- `pages/game.php` loads a route from PHP, converts it to `routeData`, and then runs the actual game logic client-side: GPS watching, proximity checks, challenge validation, marker state, and progress UI.
- Registered-player progress is persisted only when the client calls `actions/track-visit.php`, which inserts into `node_visits` and records route completion in `route_completions`.
- `pages/routes.php` and `pages/player/dashboard.php` query those progress tables to show available, in-progress, and completed routes.

### Shared services and cross-cutting pieces

- `config/constants.php` loads `.env`, defines DB credentials, route/session constants, upload size limits, reCAPTCHA keys, and the `REQUIRE_GPS_PROXIMITY` dev toggle.
- `includes/_feedback_widget.php` is a shared modal used across pages; `actions/submit-feedback.php` validates reCAPTCHA, stores the submission in the DB, and sends email.
- `actions/upload-media.php` is the Summernote upload backend; uploaded files are stored under `uploads/{user_public_id}/`.

### Locales

- `includes/locales/` contains locale files that return translation arrays. `havu_locale.class.php` loads the appropriate file based on the user's session and provides the `__()` function for fetching translations.
- The Finnish locale (`fi.php`) is considered to always be the master. Every other locale file should have the same keys as `fi.php`, and missing keys will fall back to Finnish. When adding new UI text, add it first to `fi.php` and then propagate to other locales.

## Key conventions

- **Keep user-visible UI text in Finnish.** Existing admin/player flows, editor labels, and gameplay messages are Finnish even when comments or internal exception text are English.
- **Use public UUIDs at page/API boundaries and integer IDs for relational writes.** URLs, sessions, and public route references use `public_id`, while `node_route_cross`, `node_visits`, and `route_completions` use internal numeric IDs.
- **Open and close DB connections per method/request.** `Tools::getDb()` creates a new `mysqli` connection; classes and actions do not share a long-lived connection.
- **Follow the ORM-style loader pattern already used here.** `User`, `Route`, and `Node` constructors load their database state immediately; pages commonly create an object from an ID/helper instead of manually mapping rows.
- **Treat route node order as data in `node_route_cross.order_number`.** Route rendering and gameplay depend on that order for start/finish markers, route lines, and waypoint sequence.
- **Route edits replace the node set instead of patching nodes in place.** `actions/update-route.php` deletes existing cross-links, recreates nodes from submitted JSON, and cleans up orphaned rows afterward.
- **Rich node content is authored as HTML.** Summernote content is stored in `nodes.content` and rendered back into popups; media uploads go through `actions/upload-media.php`, not direct file references.
- **Challenge definitions live in `nodes.challenge_data` JSON.** Admin pages build that JSON via `js/challenge-panel.js`; `pages/game.php` is the consumer and enforces the challenge before check-in.
- **Most write actions report status through session flash messages.** The main exception is JSON endpoints such as `track-visit.php`, `upload-media.php`, and `submit-feedback.php`.
- **For local/manual gameplay testing, `REQUIRE_GPS_PROXIMITY` in `config/constants.php` can disable the distance gate without rewriting game logic.**
