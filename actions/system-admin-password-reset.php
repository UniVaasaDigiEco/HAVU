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
if ($target_public_id === '') {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Missing target user.',
    ];
    header('Location: ' . $return_to);
    exit;
}

try {
    $target_user = Tools::getSystemAdminUserByPublicId($target_public_id);
    if ((int)$target_user['is_active'] !== 1) {
        $_SESSION['flash_messages'][] = [
            'type' => 'error',
            'code' => 0,
            'message' => 'Cannot send reset link to an inactive user.',
        ];
        header('Location: ' . $return_to);
        exit;
    }

    $db = Tools::getDb();

    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

    $stmt_token = $db->prepare(
        'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, requested_ip, requested_user_agent)
         VALUES (?, ?, (NOW() + INTERVAL 60 MINUTE), ?, ?)'
    );
    $stmt_token->bind_param('isss', $target_user['id'], $token_hash, $ip, $user_agent);
    $stmt_token->execute();
    $stmt_token->close();

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $reset_link = $scheme . '://' . $host . ROOT_DIR . 'reset-password.php?token=' . urlencode($token);

    $subject = t('actions.password_reset.email_subject');
    $message = t('actions.password_reset.email_body', ['reset_link' => $reset_link]);
    $mail_host = explode(':', $host)[0];
    $headers = "From: no-reply@{$mail_host}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    @mail((string)$target_user['email'], $subject, $message, $headers);

    error_log('system_admin_password_reset ' . json_encode([
        'actor_public_id' => $_SESSION['user_public_id'] ?? null,
        'target_public_id' => $target_user['public_id'],
    ], JSON_UNESCAPED_SLASHES));

    $db->close();

    $_SESSION['flash_messages'][] = [
        'type' => 'success',
        'code' => 0,
        'message' => 'Password reset link sent to user email.',
    ];
} catch (Exception $e) {
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }

    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Failed to issue password reset link. Check reset tables and mail setup.',
    ];
}

header('Location: ' . $return_to);
exit;
