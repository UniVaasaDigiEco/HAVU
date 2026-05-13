<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');

Security::initSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_DIR . 'pages/player/dashboard.php');
    exit;
}

if (empty($_SESSION['user_public_id'])) {
    header('Location: ' . ROOT_DIR . 'login.php');
    exit;
}

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$new_password_confirm = $_POST['new_password_confirm'] ?? '';

if ($current_password === '' || $new_password === '' || $new_password_confirm === '') {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message_key' => 'actions.account.password_missing_fields',
    ];
    header('Location: ' . ROOT_DIR . 'pages/player/dashboard.php');
    exit;
}

if (mb_strlen($new_password) < 8) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message_key' => 'actions.account.password_too_short',
    ];
    header('Location: ' . ROOT_DIR . 'pages/player/dashboard.php');
    exit;
}

if ($new_password !== $new_password_confirm) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message_key' => 'actions.account.password_mismatch',
    ];
    header('Location: ' . ROOT_DIR . 'pages/player/dashboard.php');
    exit;
}

try {
    $user = Tools::getUserWithPublicId($_SESSION['user_public_id']);

    if (!password_verify($current_password, $user->getPasswordHash())) {
        $_SESSION['flash_messages'][] = [
            'type' => 'error',
            'code' => 0,
            'message_key' => 'actions.account.password_current_incorrect',
        ];
        header('Location: ' . ROOT_DIR . 'pages/player/dashboard.php');
        exit;
    }

    $db = Tools::getDb();
    $sql = 'UPDATE users SET password_hash = ? WHERE id = ?';
    $stmt = $db->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $db->error);
    }

    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $user_id = $user->getId();
    $stmt->bind_param('si', $new_hash, $user_id);

    if (!$stmt->execute()) {
        throw new RuntimeException('Execute failed: ' . $stmt->error);
    }

    $stmt->close();
    $db->close();

    $_SESSION['flash_messages'][] = [
        'type' => 'success',
        'code' => 0,
        'message_key' => 'actions.account.password_updated',
    ];
} catch (Exception $e) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message_key' => 'actions.account.password_update_failed',
    ];
}

header('Location: ' . ROOT_DIR . 'pages/player/dashboard.php');
exit;
