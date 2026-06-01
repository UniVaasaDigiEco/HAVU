<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');
require_once(__DIR__ . '/../classes/system_admin_messagecenter.class.php');

Security::initSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_DIR . 'pages/player/dashboard.php');
    exit;
}

if (empty($_SESSION['user_public_id'])) {
    header('Location: ' . ROOT_DIR . 'login.php');
    exit;
}

$delete_confirm_input = trim($_POST['delete_confirm_input'] ?? '');

if ($delete_confirm_input !== 'DELETE') {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message_key' => 'actions.account.delete_confirmation_invalid',
    ];
    header('Location: ' . ROOT_DIR . 'pages/player/dashboard.php');
    exit;
}

try {
    $user = Tools::getUserWithPublicId($_SESSION['user_public_id']);
    $user_id = $user->getId();
    $user_public_id = $_SESSION['user_public_id'];

    $db = Tools::getDb();
    $db->begin_transaction();

    // Collect route IDs created by this user so related progress/cross rows can be removed safely.
    $route_ids = [];
    $stmt_routes = $db->prepare('SELECT id FROM routes WHERE user_id = ?');
    $stmt_routes->bind_param('s', $user_public_id);
    $stmt_routes->execute();
    $stmt_routes->bind_result($route_id);
    while ($stmt_routes->fetch()) {
        $route_ids[] = (int)$route_id;
    }
    $stmt_routes->close();

    if (!empty($route_ids)) {
        $placeholders = implode(',', array_fill(0, count($route_ids), '?'));
        $route_types = str_repeat('i', count($route_ids));

        $sql_visits_by_route = "DELETE FROM node_visits WHERE route_id IN ($placeholders)";
        $stmt_visits_by_route = $db->prepare($sql_visits_by_route);
        $stmt_visits_by_route->bind_param($route_types, ...$route_ids);
        $stmt_visits_by_route->execute();
        $stmt_visits_by_route->close();

        $sql_completions_by_route = "DELETE FROM route_completions WHERE route_id IN ($placeholders)";
        $stmt_completions_by_route = $db->prepare($sql_completions_by_route);
        $stmt_completions_by_route->bind_param($route_types, ...$route_ids);
        $stmt_completions_by_route->execute();
        $stmt_completions_by_route->close();

        $sql_cross = "DELETE FROM node_route_cross WHERE route_id IN ($placeholders)";
        $stmt_cross = $db->prepare($sql_cross);
        $stmt_cross->bind_param($route_types, ...$route_ids);
        $stmt_cross->execute();
        $stmt_cross->close();

        $sql_routes = "DELETE FROM routes WHERE id IN ($placeholders)";
        $stmt_delete_routes = $db->prepare($sql_routes);
        $stmt_delete_routes->bind_param($route_types, ...$route_ids);
        $stmt_delete_routes->execute();
        $stmt_delete_routes->close();

        // Remove orphaned nodes left after deleting node_route_cross rows.
        $db->query('DELETE n FROM nodes n LEFT JOIN node_route_cross nrc ON n.id = nrc.node_id WHERE nrc.id IS NULL');
    }

    $stmt_visits = $db->prepare('DELETE FROM node_visits WHERE user_id = ?');
    $stmt_visits->bind_param('i', $user_id);
    $stmt_visits->execute();
    $stmt_visits->close();

    $stmt_completions = $db->prepare('DELETE FROM route_completions WHERE user_id = ?');
    $stmt_completions->bind_param('i', $user_id);
    $stmt_completions->execute();
    $stmt_completions->close();

    $stmt_feedback = $db->prepare('DELETE FROM feedback_submissions WHERE user_id = ?');
    $stmt_feedback->bind_param('i', $user_id);
    $stmt_feedback->execute();
    $stmt_feedback->close();

    SystemAdminMessageCenter::nullSenderReferences($user_id);

    $stmt_user = $db->prepare('DELETE FROM users WHERE id = ?');
    $stmt_user->bind_param('i', $user_id);
    $stmt_user->execute();

    if ($stmt_user->affected_rows < 1) {
        throw new RuntimeException('Failed to delete user');
    }

    $stmt_user->close();
    $db->commit();
    $db->close();

    Security::logout();
    header('Location: ' . ROOT_DIR . 'login.php?account_deleted=1');
    exit;
} catch (Exception $e) {
    if (isset($db) && $db instanceof mysqli) {
        $db->rollback();
        $db->close();
    }

    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message_key' => 'actions.account.delete_failed',
    ];

    header('Location: ' . ROOT_DIR . 'pages/player/dashboard.php');
    exit;
}
