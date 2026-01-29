<?php
// Load database credentials from environment file
$env = require __DIR__ . '/../.env';

const ROOT_DIR = '/InnoWind/';
const ICON_PATH = ROOT_DIR . 'images/logos/InnoWind_icon.png';

const SESSION_NAME = "InnoWindSession";

// Database configuration from environment file
define('DB_HOST', $env['DB_HOST']);
define('DB_NAME', $env['DB_NAME']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);