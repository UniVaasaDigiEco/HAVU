<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/security.class.php');
require_once(__DIR__ . '/../classes/tools.class.php');

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
$full_name = trim($_POST['full_name'] ?? '');
$is_active_raw = $_POST['is_active'] ?? '1';
$is_active = $is_active_raw === '0' ? 0 : 1;

if ($target_public_id === '') {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Missing target user.',
    ];
    header('Location: ' . $return_to);
    exit;
}

if ($full_name === '' || mb_strlen($full_name) < 2 || mb_strlen($full_name) > 120) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Name must be between 2 and 120 characters.',
    ];
    header('Location: ' . $return_to);
    exit;
}

$current_public_id = (string)($_SESSION['user_public_id'] ?? '');
if ($target_public_id === $current_public_id && $is_active === 0) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'You cannot deactivate your own account from this panel.',
    ];
    header('Location: ' . $return_to);
    exit;
}

try {
    $target_user = Tools::getSystemAdminUserByPublicId($target_public_id);

    if ($is_active === 0
        && $target_user['is_active'] === 1
        && Security::isSystemAdminPublicId($target_user['public_id'])
        && Security::getActiveSystemAdminCount() <= 1
    ) {
        $_SESSION['flash_messages'][] = [
            'type' => 'error',
            'code' => 0,
            'message' => 'Cannot deactivate the last active system administrator.',
        ];
        header('Location: ' . $return_to);
        exit;
    }

    $db = Tools::getDb();
    $sql = 'UPDATE users SET full_name = ?, is_active = ? WHERE id = ?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('sii', $full_name, $is_active, $target_user['id']);
    $stmt->execute();
    $stmt->close();
    $db->close();

    $_SESSION['flash_messages'][] = [
        'type' => 'success',
        'code' => 0,
        'message' => 'User updated successfully.',
    ];
} catch (Exception $e) {
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }

    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Failed to update user.',
    ];
}

header('Location: ' . $return_to);
exit;
