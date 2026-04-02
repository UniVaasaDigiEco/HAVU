<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');

Security::initSession();

if (empty($_SESSION['user_public_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

$route_public_id = trim($_POST['route_public_id'] ?? '');

if (empty($route_public_id)) {
    header('Location: ../pages/admin/dashboard.php');
    exit;
}

try {
    $db = Tools::getDb();

    // Toggle is_published only for routes owned by this admin
    $sql = "UPDATE routes SET is_published = (1 - is_published) WHERE public_id = ? AND user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ss', $route_public_id, $_SESSION['user_public_id']);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Reittiä ei löydy tai sinulla ei ole oikeutta muokata sitä.');
    }

    // Read the new state to show the right message
    $stmt->close();
    $sql2 = "SELECT is_published FROM routes WHERE public_id = ?";
    $stmt2 = $db->prepare($sql2);
    $stmt2->bind_param('s', $route_public_id);
    $stmt2->execute();
    $stmt2->bind_result($new_state);
    $stmt2->fetch();
    $stmt2->close();
    $db->close();

    $_SESSION['flash_messages'][] = [
        'type'    => 'success',
        'code'    => 0,
        'message' => $new_state ? 'Reitti on nyt julkinen.' : 'Reitti on nyt yksityinen.',
    ];

} catch (Exception $e) {
    $_SESSION['flash_messages'][] = [
        'type'    => 'error',
        'code'    => 0,
        'message' => $e->getMessage(),
    ];
}

header('Location: ../pages/admin/dashboard.php');
exit;