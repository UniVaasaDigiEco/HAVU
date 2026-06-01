# Copilot Instructions

## Fast start for agents

| Task | Command | Notes |
| --- | --- | --- |
| Install PHP dependencies | `composer install` | Required for `ramsey/uuid` and autoloading. |
| Install frontend dependencies | `npm install` | `node_modules` is committed, but this is still the setup command for fresh environments. |
| Import database schema | Manual SQL import | Import `_SQL/havu-sql-structure.sql` and then `_SQL/add_messages_table.sql` on fresh setups. |
| Compile SCSS once | `npm run scss-compile` | Builds `css/scss` into `css/bs-custom.css`. |
| Watch SCSS | `npm run scss-watch` | Rebuilds on file changes. |

Lint and tests status:
- No runnable lint script exists in `package.json` or `composer.json`.
- Run lint manually with `npx eslint .` when needed.
- No automated test suite exists.
- `npm test` is intentionally a placeholder and always exits with an error.

## Architecture in one screen

- App style: server-rendered PHP pages under XAMPP/Laragon, with direct asset loading from `node_modules`.
- Typical request flow:
	1. Page/action calls `Security::initSession()`.
	2. Data access goes through `Tools` helpers and model constructors (`User`, `Route`, `Node`).
	3. Interactive pages serialize PHP model state into JavaScript for map/game logic.
	4. Write actions use prepared statements and usually report via `$_SESSION['flash_messages']`.

Primary flows:
- Route authoring: `pages/admin/new-route.php`, `pages/admin/edit-route.php`, `js/challenge-panel.js`, `actions/create_route.php`, `actions/update-route.php`.
- Gameplay/progress: `pages/game.php`, `actions/track-visit.php`, `pages/routes.php`, `pages/player/dashboard.php`.
- Cross-cutting: `config/constants.php`, `actions/upload-media.php`, `includes/_feedback_widget.php`, `actions/submit-feedback.php`.

## Project-specific conventions

- Keep player/admin UI strings Finnish-first. Add new locale keys to `includes/locales/fi.php` first, then propagate to other locales.
- Translation access uses `t()` from `includes/i18n.php`; locale loading/fallback is handled in `classes/havu_locale.class.php`.
- Use `public_id` UUIDs at page/API boundaries; use numeric IDs for relation tables (`node_route_cross`, `node_visits`, `route_completions`).
- Route edits replace node links as a full set; do not patch node ordering ad hoc.
- `nodes.challenge_data` is JSON authored in admin pages and validated/consumed in gameplay.
- `Tools::getDb()` creates per-call MySQLi connections (with localhost-to-127.0.0.1 fallback behavior).

## Pitfalls to avoid

- There are two similarly named update endpoints: `actions/update-route.php` (current, localized message keys, richer validation) and `actions/update_route.php` (older legacy file). Prefer the hyphenated file for new work.
- Proximity behavior is controlled by both route-level threshold and global `REQUIRE_GPS_PROXIMITY` in `config/constants.php`.
- JSON endpoints are exceptions to redirect-with-flash patterns (`actions/track-visit.php`, `actions/upload-media.php`, `actions/submit-feedback.php`).
- The messages feature depends on `_SQL/add_messages_table.sql`; this table is not created by the base schema import alone.
- `.env.example` contains `HOME_URL`, but runtime home URL is derived in `config/constants.php`; do not depend on `.env` `HOME_URL` for new logic.

## Link, do not duplicate docs

- Product and setup overview: [README.md](../README.md)
- End-user/admin manuals: [_DOCS/_MANUALS](../_DOCS/_MANUALS)
- GPS behavior notes: [_DOCS/GPS_Threshold_Testing.md](../_DOCS/GPS_Threshold_Testing.md)
- Message widget integration checklist: [_DOCS/message_widget_integration_checklist.md](../_DOCS/message_widget_integration_checklist.md)
- Superpowers implementation notes/plans: [docs/superpowers](../docs/superpowers)
