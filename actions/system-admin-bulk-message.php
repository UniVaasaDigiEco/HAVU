<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/security.class.php');
require_once(__DIR__ . '/../classes/tools.class.php');

Security::initSession();

$return_to = ROOT_DIR . 'system-admin/bulk-message.php';

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
if (!Security::validateCsrfToken($csrf_token, 'system_admin_bulk_message')) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Invalid request token. Please try again.',
    ];
    header('Location: ' . $return_to);
    exit;
}

$recipient_scope = trim($_POST['recipient_scope'] ?? '');
if (!in_array($recipient_scope, ['active', 'all', 'selected'], true)) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Invalid recipient scope.',
    ];
    header('Location: ' . $return_to);
    exit;
}

$subject = trim($_POST['subject'] ?? '');
$body    = trim($_POST['body'] ?? '');

if ($subject === '' || strlen($subject) > 200) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Subject must be between 1 and 200 characters.',
    ];
    header('Location: ' . $return_to);
    exit;
}

if ($body === '' || strlen($body) > 10000) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Message body must be between 1 and 10 000 characters.',
    ];
    header('Location: ' . $return_to);
    exit;
}

// When sending to all users (including inactive), require explicit confirmation
if ($recipient_scope === 'all') {
    $confirm_text = trim($_POST['confirm_text'] ?? '');
    if ($confirm_text !== 'SEND ALL') {
        $_SESSION['flash_messages'][] = [
            'type' => 'error',
            'code' => 0,
            'message' => 'Confirmation text did not match. Type "SEND ALL" to proceed.',
        ];
        header('Location: ' . $return_to);
        exit;
    }
}

$selected_public_ids = [];
if ($recipient_scope === 'selected') {
    $selected_raw = $_POST['selected_user_public_ids'] ?? [];
    if (!is_array($selected_raw)) {
        $selected_raw = [];
    }

    foreach ($selected_raw as $selected_item) {
        if (!is_string($selected_item)) {
            continue;
        }

        $selected_item = trim($selected_item);
        if ($selected_item !== '') {
            $selected_public_ids[$selected_item] = true;
        }
    }

    if (empty($selected_public_ids)) {
        $_SESSION['flash_messages'][] = [
            'type' => 'error',
            'code' => 0,
            'message' => 'Select at least one user when using Selected users.',
        ];
        header('Location: ' . $return_to);
        exit;
    }
}

try {
    $db = Tools::getDb();

    if ($recipient_scope === 'active') {
        $stmt = $db->prepare('SELECT public_id, email, full_name FROM users WHERE is_active = 1 ORDER BY id ASC');
    } else {
        $stmt = $db->prepare('SELECT public_id, email, full_name FROM users ORDER BY id ASC');
    }

    $stmt->execute();
    $stmt->bind_result($r_public_id, $r_email, $r_full_name);

    $recipients = [];
    while ($stmt->fetch()) {
        if ($recipient_scope === 'selected' && !isset($selected_public_ids[(string)$r_public_id])) {
            continue;
        }

        $recipients[] = ['email' => (string)$r_email, 'full_name' => (string)$r_full_name];
    }
    $stmt->close();
    $db->close();

    if (empty($recipients)) {
        $_SESSION['flash_messages'][] = [
            'type' => 'error',
            'code' => 0,
            'message' => 'No recipients found for the selected scope.',
        ];
        header('Location: ' . $return_to);
        exit;
    }

    $scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $mail_host = explode(':', $host)[0];
    $from      = 'no-reply@' . $mail_host;
    $headers   = "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8\r\n";

    $sent  = 0;
    $failed = 0;

    foreach ($recipients as $recipient) {
        $personalised_body = $body;
        $result = @mail($recipient['email'], $subject, $personalised_body, $headers);
        if ($result) {
            $sent++;
        } else {
            $failed++;
        }
    }

    error_log('system_admin_bulk_message ' . json_encode([
        'actor_public_id'  => $_SESSION['user_public_id'] ?? null,
        'recipient_scope'  => $recipient_scope,
        'recipients_total' => count($recipients),
        'sent'             => $sent,
        'failed'           => $failed,
        'subject'          => $subject,
    ], JSON_UNESCAPED_SLASHES));

    if ($failed === 0) {
        $_SESSION['flash_messages'][] = [
            'type' => 'success',
            'code' => 0,
            'message' => 'E-mail sent to ' . $sent . ' recipient' . ($sent !== 1 ? 's' : '') . '.',
        ];
    } else {
        $_SESSION['flash_messages'][] = [
            'type' => 'warning',
            'code' => 0,
            'message' => 'Sent to ' . $sent . ' recipient' . ($sent !== 1 ? 's' : '') . '. Failed to send to ' . $failed . '.',
        ];
    }
} catch (Exception $e) {
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }

    error_log('system_admin_bulk_message_error ' . $e->getMessage());

    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Failed to send bulk message. Check server logs for details.',
    ];
}

header('Location: ' . $return_to);
exit;
