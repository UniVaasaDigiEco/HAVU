<?php
/**
 * Logout action
 *
 * Log the user out and clear the session data
 */
require_once(__DIR__ . '/../config/constants.php');

// Start the session so we can destroy it
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Clear all session variables
$_SESSION = [];

// Delete the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Redirect to the game portal
header('Location:'.  HOME_URL);
exit;
