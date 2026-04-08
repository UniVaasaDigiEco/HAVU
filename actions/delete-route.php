<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/route.class.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');

Security::initSession();

if (empty($_SESSION['user_public_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ' . ROOT_DIR . 'login.php');
    exit;
}

$route_public_id = trim($_POST['route_public_id'] ?? '');

if (empty($route_public_id)) {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'code' => 0, 'message' => 'Reitti-ID puuttuu.'];
    header('Location: ' . ROOT_DIR . 'pages/admin/dashboard.php');
    exit;
}

try {
    $route = Tools::getRouteByPublicId($route_public_id);
} catch (Exception $e) {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'code' => 0, 'message' => 'Reittiä ei löydy.'];
    header('Location: ' . ROOT_DIR . 'pages/admin/dashboard.php');
    exit;
}

// Verify that this route belongs to the currently logged-in admin
$db = Tools::getDb();
$stmt = $db->prepare("SELECT id FROM routes WHERE public_id = ? AND user_id = ?");
$stmt->bind_param('ss', $route_public_id, $_SESSION['user_public_id']);
$stmt->execute();
$stmt->store_result();
$owns_route = $stmt->num_rows > 0;
$stmt->close();
$db->close();

if (!$owns_route) {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'code' => 0, 'message' => 'Ei oikeutta poistaa tätä reittiä.'];
    header('Location: ' . ROOT_DIR . 'pages/admin/dashboard.php');
    exit;
}

try {
    $route->delete();
    $_SESSION['flash_messages'][] = ['type' => 'success', 'code' => 0, 'message' => 'Reitti poistettu onnistuneesti.'];
} catch (Exception $e) {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'code' => 0, 'message' => 'Reitin poistaminen epäonnistui.'];
}

header('Location: ' . ROOT_DIR . 'pages/admin/dashboard.php');
exit;
