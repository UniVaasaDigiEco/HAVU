<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/security.class.php');
require_once(__DIR__ . '/../classes/system_admin_messagecenter.class.php');

Security::initSession();

function resolveSystemAdminMessageReturnTo(?string $candidate): string
{
    $default = ROOT_DIR . 'system-admin/messages.php';

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
    if (!is_string($path) || strpos($path, ROOT_DIR . 'system-admin/') !== 0) {
        return $default;
    }

    return $candidate;
}

$return_to = resolveSystemAdminMessageReturnTo($_POST['return_to'] ?? null);

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
if (!Security::validateCsrfToken($csrf_token, 'system_admin_messages')) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Invalid request token. Please try again.',
    ];
    header('Location: ' . $return_to);
    exit;
}

$message_id = (int)($_POST['message_id'] ?? 0);
$mark_as = trim($_POST['mark_as'] ?? '');

if ($message_id < 1 || !in_array($mark_as, ['read', 'unread'], true)) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Invalid message state request.',
    ];
    header('Location: ' . $return_to);
    exit;
}

try {
    SystemAdminMessageCenter::setReadState($message_id, $mark_as === 'read');
    $_SESSION['flash_messages'][] = [
        'type' => 'success',
        'code' => 0,
        'message' => $mark_as === 'read' ? 'Message marked as read.' : 'Message marked as unread.',
    ];
} catch (Exception $e) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Failed to update message state.',
    ];
}

header('Location: ' . $return_to);
exit;
