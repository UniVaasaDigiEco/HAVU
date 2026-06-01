<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/security.class.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/system_admin_messagecenter.class.php');

Security::initSession();

function resolveSystemAdminReturnTo(?string $candidate): string
{
    $default = ROOT_DIR . 'system-admin/user-management.php';

    if (!is_string($candidate)) {
        return $default;
    }

    $candidate = trim($candidate);
    if ($candidate === '' || preg_match('/[\r\n]/', $candidate)) {
        return $default;
    }

    $parsed = parse_url($candidate);
    if ($parsed === false || isset($parsed['scheme']) || isset($parsed['host']) || isset($parsed['user']) || isset($parsed['pass'])) {
        return $default;
    }

    $path = $parsed['path'] ?? '';
    if (!is_string($path) || !str_starts_with($path, ROOT_DIR . 'system-admin/')) {
        return $default;
    }

    return $candidate;
}

function hardDeleteUserById(int $user_id, string $user_public_id): void
{
    $db = Tools::getDb();

    try {
        $db->begin_transaction();

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
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    } finally {
        $db->close();
    }
}

$return_to = resolveSystemAdminReturnTo($_POST['return_to'] ?? null);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $return_to);
    exit;
}

if (empty($_SESSION['user_public_id']) || !Security::isCurrentUserSystemAdmin()) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'System admin access is required.',
    ];
    header('Location: ' . ROOT_DIR . 'login.php');
    exit;
}

$csrf_token = trim($_POST['csrf_token'] ?? '');
if (!Security::validateCsrfToken($csrf_token, 'system_admin_user_management')) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Invalid request token. Please try again.',
    ];
    header('Location: ' . $return_to);
    exit;
}

$target_public_id = trim($_POST['user_public_id'] ?? '');
$mode = trim($_POST['mode'] ?? 'deactivate');
$confirm_text = trim($_POST['confirm_text'] ?? '');

if ($target_public_id === '') {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Missing target user.',
    ];
    header('Location: ' . $return_to);
    exit;
}

$current_public_id = (string)($_SESSION['user_public_id'] ?? '');
if ($target_public_id === $current_public_id && in_array($mode, ['deactivate', 'hard_delete'], true)) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'You cannot remove or deactivate your own account from this panel.',
    ];
    header('Location: ' . $return_to);
    exit;
}

try {
    $target_user = Tools::getSystemAdminUserByPublicId($target_public_id);

    if (
        in_array($mode, ['deactivate', 'hard_delete'], true)
        && (int)$target_user['is_active'] === 1
        && Security::isSystemAdminPublicId((string)$target_user['public_id'])
        && Security::getActiveSystemAdminCount() <= 1
    ) {
        $_SESSION['flash_messages'][] = [
            'type' => 'error',
            'code' => 0,
            'message' => 'Cannot remove or deactivate the last active system administrator.',
        ];
        header('Location: ' . $return_to);
        exit;
    }

    if ($mode === 'activate') {
        $db = Tools::getDb();
        $stmt = $db->prepare('UPDATE users SET is_active = 1 WHERE id = ?');
        $stmt->bind_param('i', $target_user['id']);
        $stmt->execute();
        $stmt->close();
        $db->close();

        $_SESSION['flash_messages'][] = [
            'type' => 'success',
            'code' => 0,
            'message' => 'User reactivated successfully.',
        ];
    } elseif ($mode === 'deactivate') {
        $db = Tools::getDb();
        $stmt = $db->prepare('UPDATE users SET is_active = 0 WHERE id = ?');
        $stmt->bind_param('i', $target_user['id']);
        $stmt->execute();
        $stmt->close();
        $db->close();

        $_SESSION['flash_messages'][] = [
            'type' => 'success',
            'code' => 0,
            'message' => 'User deactivated successfully.',
        ];
    } elseif ($mode === 'hard_delete') {
        if ($confirm_text !== 'DELETE') {
            $_SESSION['flash_messages'][] = [
                'type' => 'error',
                'code' => 0,
                'message' => 'Hard delete requires typing DELETE in the confirmation field.',
            ];
            header('Location: ' . $return_to);
            exit;
        }

        hardDeleteUserById((int)$target_user['id'], (string)$target_user['public_id']);

        error_log('system_admin_hard_delete_user ' . json_encode([
            'actor_public_id' => $_SESSION['user_public_id'] ?? null,
            'target_public_id' => $target_user['public_id'],
        ], JSON_UNESCAPED_SLASHES));

        $_SESSION['flash_messages'][] = [
            'type' => 'success',
            'code' => 0,
            'message' => 'User permanently deleted.',
        ];
    } else {
        $_SESSION['flash_messages'][] = [
            'type' => 'error',
            'code' => 0,
            'message' => 'Unknown remove mode.',
        ];
    }
} catch (Exception $e) {
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }

    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'User action failed.',
    ];
}

header('Location: ' . $return_to);
exit;
