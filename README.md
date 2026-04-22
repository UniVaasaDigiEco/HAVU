# HAVU-trailgame

HAVU-trailgame is a geocaching-style web application for creating and playing interactive outdoor routes. Admins build routes with GPS checkpoints, rich content, and optional challenges; players walk the route and check in at each checkpoint when they are close enough.

The app is localized in Finnish, English, and Swedish. The localized product names are **HAVU-polkupeli** (Finnish) and **HAVU-stigspelet** (Swedish).

## Features

### Player
- 📍 **GPS-based gameplay** with browser Geolocation API and Leaflet maps
- 🗺️ **Public route browser** for starting any published route without an account
- 🎯 **Proximity-based check-ins** with a 20 m default interaction range
- ❓ **Checkpoint challenges** with multiple-choice and text-answer support
- 💬 **Rich checkpoint content** with formatted text, images, and embedded media
- ✅ **Progress tracking** for registered players, including checkpoint visits and completed routes
- 🌍 **Language switcher** for Finnish, English, and Swedish
- 📨 **Feedback widget** for contact requests, bug reports, and feature suggestions

### Admin
- 🛠️ **Route management** for creating, editing, publishing, and deleting routes
- 🗺️ **Map-based checkpoint editor** with drag-and-drop positioning and route ordering
- 🖊️ **Summernote WYSIWYG editor** for checkpoint content
- 📂 **Media uploads** for images and videos stored under the creator's upload directory
- ❓ **Challenge authoring** for checkpoint-specific tasks
- 🧪 **Route testing view** from the admin area

## Tech Stack

- **Backend**: PHP 8.4, MySQLi, Composer (`ramsey/uuid`)
- **Frontend**: Bootstrap 5, Bootstrap Icons, jQuery, Leaflet.js, Summernote
- **Localization**: PHP-based translation files (`fi`, `en`, `sv`)
- **Security / integrations**: Google reCAPTCHA v3, CSP/security headers on gameplay pages
- **Server**: XAMPP (Apache + MySQL)
- **Styling**: SCSS compiled to `css/bs-custom.css`

## Setup

### Requirements

- XAMPP (Apache + MySQL + PHP 8.4)
- Composer
- Node.js / npm (for SCSS compilation)

### Installation

1. Clone or copy the project into `xampp/htdocs/HavuGamification/`.
2. Import the database schema:
   ```sql
   _SQL/havu_structure.sql
   ```
3. Copy `.env.example` to `.env` and fill in the environment values:
   ```php
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
4. Install PHP dependencies:
   ```bash
   composer install
   ```
5. Install frontend dependencies if needed in a fresh environment:
   ```bash
   npm install
   ```
6. Open the app in your browser:
   ```text
   http://localhost/HavuGamification/
   ```

### SCSS Compilation

```bash
npm run scss-compile
npm run scss-watch
```

## Usage

### Admin

1. Log in at `http://localhost/HavuGamification/login.php`.
2. Open the admin dashboard and create a new route.
3. Set the route title, description, visibility, and publication date.
4. Add checkpoints on the map, drag them into place, and reorder them as needed.
5. Edit each checkpoint's title, content, and optional challenge.
6. Upload images or videos through the Summernote editor.
7. Save the route and test it from the dashboard.

### Player (anonymous)

1. Open `http://localhost/HavuGamification/`.
2. Choose a published route from the route list.
3. Allow location access when prompted.
4. Move close to a checkpoint to open its content and complete any challenge.
5. Mark checkpoints as visited to finish the route.

### Player (registered)

1. Register at `register.php` or log in at `login.php`.
2. Use the player dashboard to view available, in-progress, and completed routes.
3. Play routes normally; checkpoint visits and route completions are stored automatically.

## Media Uploads

Uploaded files are stored under:

```text
uploads/{user_public_id}/{uuid}.{ext}
```

PHP execution is disabled in the uploads directory via `.htaccess`.

| Type  | Formats              | Default max size |
| --- | --- | --- |
| Image | JPEG, PNG, GIF, WebP | 10 MB |
| Video | MP4, WebM, MOV | 100 MB |

## Configuration

- `pages/game.php`
  - `PROXIMITY_THRESHOLD` controls the gameplay distance threshold (currently 20 m)
  - `UPDATE_INTERVAL` controls how often the player's GPS position is refreshed
- `config/constants.php`
  - `REQUIRE_GPS_PROXIMITY` can be set to `false` for local/manual testing
  - upload size limits and reCAPTCHA keys are loaded from `.env`
- Localization is handled through `config/locales/*.php`, and users can switch language via the shared language menu

## Development Notes

- The app is server-rendered PHP running directly under XAMPP.
- `node_modules/` is committed and assets are served directly from it.
- There is no automated lint or test suite configured in this repository.

## Authors

Jyri Nieminen & Vaiva Stanisauskaite

## License

ISC
