<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');

header('Content-Type: application/json');

Security::initSession();

function visitError(int $status, string $key): never
{
    http_response_code($status);
    echo json_encode(['error' => t($key)]);
    exit;
}

if (empty($_SESSION['user_public_id'])) {
    visitError(401, 'actions.track_visit.not_authenticated');
}

$node_id          = isset($_POST['node_id']) ? (int)$_POST['node_id'] : 0;
$route_public_id  = trim($_POST['route_public_id'] ?? '');

if ($node_id <= 0 || empty($route_public_id)) {
    visitError(400, 'actions.track_visit.invalid_parameters');
}

try {
    $user_id = Tools::getUserIdByPublicId($_SESSION['user_public_id']);
} catch (Exception $e) {
    visitError(401, 'actions.track_visit.user_not_found');
}

$db = Tools::getDb();

try {
    // Resolve route internal ID
    $stmt = $db->prepare("SELECT id FROM routes WHERE public_id = ?");
    $stmt->bind_param('s', $route_public_id);
    $stmt->execute();
    $stmt->bind_result($route_id);
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        visitError(404, 'actions.track_visit.route_not_found');
    }
    $stmt->fetch();
    $stmt->close();

    // Verify this node actually belongs to this route
    $stmt = $db->prepare("SELECT id FROM node_route_cross WHERE node_id = ? AND route_id = ?");
    $stmt->bind_param('ii', $node_id, $route_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        visitError(400, 'actions.track_visit.node_mismatch');
    }
    $stmt->close();

    // Record visit (ignore if already visited)
    $stmt = $db->prepare("INSERT IGNORE INTO node_visits (user_id, node_id, route_id) VALUES (?, ?, ?)");
    $stmt->bind_param('iii', $user_id, $node_id, $route_id);
    $stmt->execute();
    $stmt->close();

    // Check if all nodes in this route are now visited by this user
    $stmt = $db->prepare(
        "SELECT
            (SELECT COUNT(*) FROM node_route_cross WHERE route_id = ?) AS total_nodes,
            (SELECT COUNT(*) FROM node_visits WHERE user_id = ? AND route_id = ?) AS visited_nodes"
    );
    $stmt->bind_param('iii', $route_id, $user_id, $route_id);
    $stmt->execute();
    $stmt->bind_result($total_nodes, $visited_nodes);
    $stmt->fetch();
    $stmt->close();

    $route_completed = ($total_nodes > 0 && $visited_nodes >= $total_nodes);

    if ($route_completed) {
        // Record completion (ignore if already recorded)
        $stmt = $db->prepare("INSERT IGNORE INTO route_completions (user_id, route_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $user_id, $route_id);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['visited' => true, 'route_completed' => $route_completed]);

} catch (Exception $e) {
    visitError(500, 'actions.track_visit.server_error');
} finally {
    $db->close();
}
