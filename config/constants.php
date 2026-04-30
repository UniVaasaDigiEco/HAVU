<?php
// Load database credentials from environment file
if (!file_exists(__DIR__ . '/../.env')) {
    die('Error: .env file not found. Please copy .env.example to .env and configure your credentials.');
}
$env = require __DIR__ . '/../.env';

define('ROOT_DIR', $env['ROOT_DIR'] ?? '/'); // Fallback to '/HavuGamification/' if ROOT_DIR is not set in .env
define('ICON_PATH', ROOT_DIR . 'images/logos/InnoWind_icon.png');

//Define session name
const SESSION_NAME = "HavuGamificationSession";

const ERROR_CODES = [
    0 => "",
    1 => "Wrong username or password.",
    2 => "Error creating route.",
    3 => "Error updating route."
];

const SUCCESS_CODES = [
    0 => "",
    1 => "User created successfully.",
    2 => "Route created successfully.",
    3 => "Route updated successfully."
];

const USER_TYPE_REGULAR = 1;
const USER_TYPE_ADMIN = 0;

const HOME_URL = ROOT_DIR . "index.php";

// Set to false to skip GPS proximity checks (useful for development/testing)
const REQUIRE_GPS_PROXIMITY = true;

// Distance in meters to trigger node proximity behaviors in game views
const PROXIMITY_THRESHOLD = 25;

// Database configuration from environment file
define('DB_HOST', $env['DB_HOST']);
define('DB_NAME', $env['DB_NAME']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);
define('DB_PORT', isset($env['DB_PORT']) ? (int)$env['DB_PORT'] : 3306);
define('DB_SOCKET', $env['DB_SOCKET'] ?? null);

// Upload size limits (in bytes); configured per environment in .env
define('UPLOAD_MAX_IMAGE_BYTES', ($env['UPLOAD_MAX_IMAGE_MB'] ?? 10) * 1024 * 1024);
define('UPLOAD_MAX_VIDEO_BYTES', ($env['UPLOAD_MAX_VIDEO_MB'] ?? 100) * 1024 * 1024);

// reCAPTCHA configuration from environment file
define('RECAPTCHA_SITE_KEY',   $env['RECAPTCHA_SITE_KEY']   ?? '');
define('RECAPTCHA_SECRET_KEY', $env['RECAPTCHA_SECRET_KEY'] ?? '');

$maintenance_mode = $env['MAINTENANCE_MODE'] ?? false;
if (is_string($maintenance_mode)) {
    $parsed_maintenance_mode = filter_var($maintenance_mode, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $maintenance_mode = $parsed_maintenance_mode ?? false;
}
define('MAINTENANCE_MODE', (bool)$maintenance_mode);

require_once(__DIR__ . '/../includes/i18n.php');
