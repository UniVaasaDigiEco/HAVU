<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');
require_once(__DIR__ . '/../classes/messagecenter.class.php');

Security::initSession();

header('Content-Type: application/json');

function jsonError(string $msg): never {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Invalid request method.');
}

$message_id = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

if ($message_id <= 0) {
    jsonError('Invalid message ID.');
}

if (!in_array($action, ['read', 'unread'], true)) {
    jsonError('Invalid action. Must be "read" or "unread".');
}

try {
    $message = new MessageCenter($message_id);
    
    // Verify current user is the recipient
    $current_user_id = Tools::getUserIdByPublicId($_SESSION['user_public_id'] ?? '');
    if ($message->recipient_user_id !== $current_user_id) {
        jsonError('You do not have permission to modify this message.');
    }
    
    if ($action === 'read') {
        $message->markAsRead();
    } else {
        $message->markAsUnread();
    }
    
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    jsonError('Failed to update message: ' . $e->getMessage());
}
