# HAVU Gamification - Campus Route Geocaching App

A geocaching-style map application for creating and playing interactive campus tours. Admins create routes with GPS waypoints; players walk the route and check in at each waypoint when within 50 metres.

## Features

### Player
- 📍 **GPS Tracking**: Real-time location tracking via the browser Geolocation API
- 🗺️ **Interactive Map**: Leaflet-powered map with OpenStreetMap tiles
- 🎯 **Proximity Detection**: Popup opens automatically when within 50 m of a waypoint
- 💬 **Rich Content**: Waypoints can contain formatted text, images, and videos
- ✅ **Progress Tracking**: Visual progress bar showing visited waypoints
- 🎨 **Visual Feedback**: Different icons for start, finish, visited, and unvisited waypoints
- 📏 **Distance Display**: Shows distance to the nearest unvisited waypoint
- 🎉 **Completion Screen**: Full-screen celebration shown when all waypoints are visited
- 👤 **Player Accounts**: Optional free registration to track progress across routes
- 🗂️ **Route Selection**: Public route picker — browse and start any available route without an account

### Admin
- 🛠️ **Route Management**: Create, edit, and delete routes via a web dashboard
- 🖊️ **WYSIWYG Editor**: Summernote editor for waypoint content with image and video upload
- 🗺️ **Map-based Node Placement**: Click the map to place waypoints; drag to reposition
- 📂 **Media Uploads**: Images (max 10 MB) and videos (max 100 MB) stored per-user

## Tech Stack

- **Backend**: PHP 8.4, MySQLi, Composer (`ramsey/uuid`)
- **Frontend**: Bootstrap 5, jQuery, Leaflet.js, Summernote (WYSIWYG)
- **Server**: XAMPP (Apache + MySQL)
- **CSS**: SCSS compiled to `css/bs-custom.css`

## Setup

### Requirements

- XAMPP (Apache + MySQL + PHP 8.4)
- Node.js / npm (for SCSS compilation only)
- Composer

### Installation

1. Clone/copy the project into `xampp/htdocs/HavuGamification/`
2. Import the database schema in order:
   ```
   _SQL/jansoftw_havu_structure.sql
   _SQL/add_progress_tables.sql
   ```
3. Copy `.env.example` to `.env` and fill in your database credentials:
   ```php
   return [
       'DB_HOST' => 'localhost',
       'DB_NAME' => 'jansoftw_havu',
       'DB_USER' => 'root',
       'DB_PASS' => '',
   ];
   ```
4. Install PHP dependencies:
   ```bash
   composer install
   ```
5. Node modules are committed — no `npm install` needed unless starting fresh:
   ```bash
   npm install
   ```
6. Open in browser: `http://localhost/HavuGamification/`

### SCSS Compilation

```bash
npm run scss-compile   # one-time build
npm run scss-watch     # watch mode during development
```

## Usage

### Admin

1. Log in at `http://localhost/HavuGamification/login.php`
2. From the dashboard, create a new route — give it a name, description, and publication date
3. Click the map to place waypoints; drag to reposition them
4. Click a waypoint to edit its name and content in the WYSIWYG editor
5. Upload images via the toolbar picture button, or drag-and-drop into the editor
6. Embed YouTube/Vimeo videos via the video URL button, or upload a video file via the camera icon button
7. Reorder waypoints using the up/down arrows in the waypoint list
8. Submit the form to save the route, then test it from the dashboard

### Player (anonymous)

1. Open `http://localhost/HavuGamification/` and click **Pelaa nyt**
2. Choose a route from the list and click **Aloita reitti**
3. Allow GPS access when prompted
4. Walk towards each waypoint — a popup opens automatically within 50 m
5. Click "Merkkaa käydyksi" to mark the waypoint as visited
6. A completion screen is shown when all waypoints are visited

### Player (registered)

1. Register at `register.php` or log in at `login.php`
2. After login, the player dashboard shows completed routes, in-progress routes, and available routes
3. Progress (individual node visits and route completions) is saved automatically during play

## Media Uploads

Uploaded files are stored at:
```
uploads/{user_public_id}/{uuid}.{ext}
```

PHP execution is disabled in the uploads directory via `.htaccess`. Allowed types:

| Type   | Formats               | Max size |
|--------|-----------------------|----------|
| Image  | JPEG, PNG, GIF, WebP  | 10 MB    |
| Video  | MP4, WebM, MOV        | 100 MB   |

## Configuration

Game proximity and GPS update frequency can be adjusted in `pages/game.php`:

```javascript
const PROXIMITY_THRESHOLD = 50; // metres — distance to trigger waypoint popup
```

## Authors

Jyri Nieminen & Vaiva Stanisauskaite

## License

ISC