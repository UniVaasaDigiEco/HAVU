<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');

Security::initSession();

if (empty($_SESSION['user_public_id'])) {
    header('Location: ' . ROOT_DIR . 'login.php');
    exit;
}

Security::validateCsrfToken($_POST['csrf_token'] ?? '');

$user_id = Tools::getUserIdByPublicId($_SESSION['user_public_id']);

$db = Tools::getDb();
try {
    $stmt = $db->prepare("UPDATE users SET tos_accepted = 1 WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
} finally {
    $db->close();
}

$redirect = trim($_POST['redirect'] ?? '');
$parsed = parse_url($redirect);
if ($redirect === '' || $parsed === false || isset($parsed['scheme']) || isset($parsed['host']) || !str_starts_with($parsed['path'] ?? '', ROOT_DIR)) {
    $redirect = ROOT_DIR . 'pages/player/dashboard.php';
}

header('Location: ' . $redirect);
exit;
