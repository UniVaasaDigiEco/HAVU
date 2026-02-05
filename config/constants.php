<?php
// Load database credentials from environment file
if (!file_exists(__DIR__ . '/../.env')) {
    die('Error: .env file not found. Please copy .env.example to .env and configure your credentials.');
}
$env = require __DIR__ . '/../.env';

const ROOT_DIR = '/HavuGamification/';
const ICON_PATH = ROOT_DIR . 'images/logos/InnoWind_icon.png';

//Define session name
const SESSION_NAME = "HavuGamificationSession";

const ERROR_CODES = [
    1 => "Wrong username or password."
];

const USER_TYPE_REGULAR = 1;
const USER_TYPE_ADMIN = 0;

// Database configuration from environment file
define('DB_HOST', $env['DB_HOST']);
define('DB_NAME', $env['DB_NAME']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);