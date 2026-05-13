<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');

Security::initSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_DIR . 'forgot-password.php');
    exit;
}

$token = trim($_POST['token'] ?? '');
$csrf_token = trim($_POST['csrf_token'] ?? '');
$new_password = $_POST['new_password'] ?? '';
$new_password_confirm = $_POST['new_password_confirm'] ?? '';
 $ip = $_SERVER['REMOTE_ADDR'] ?? '';

$audit = function (string $event, array $data = []) use ($ip): void {
    $payload = array_merge(['event' => $event, 'ip' => $ip], $data);
    error_log('password_reset_audit ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
};

$token_for_redirect = urlencode($token);

if (!Security::validateCsrfToken($csrf_token, 'password_reset_submit')) {
    $audit('reset_invalid_csrf');
    header('Location: ' . ROOT_DIR . 'reset-password.php?token=' . $token_for_redirect . '&error_key=' . urlencode('actions.password_reset.invalid_request'));
    exit;
}

if ($token === '' || $new_password === '' || $new_password_confirm === '') {
    $audit('reset_missing_fields');
    header('Location: ' . ROOT_DIR . 'reset-password.php?token=' . $token_for_redirect . '&error_key=' . urlencode('actions.password_reset.invalid_request'));
    exit;
}

if (mb_strlen($new_password) < 8) {
    $audit('reset_password_too_short');
    header('Location: ' . ROOT_DIR . 'reset-password.php?token=' . $token_for_redirect . '&error_key=' . urlencode('actions.password_reset.password_too_short'));
    exit;
}

if ($new_password !== $new_password_confirm) {
    $audit('reset_password_mismatch');
    header('Location: ' . ROOT_DIR . 'reset-password.php?token=' . $token_for_redirect . '&error_key=' . urlencode('actions.password_reset.password_mismatch'));
    exit;
}

try {
    $token_hash = hash('sha256', $token);
    $db = Tools::getDb();
    $db->begin_transaction();

    $stmt_token = $db->prepare(
        'SELECT id, user_id
         FROM password_reset_tokens
         WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
         LIMIT 1
         FOR UPDATE'
    );
    $stmt_token->bind_param('s', $token_hash);
    $stmt_token->execute();
    $stmt_token->bind_result($reset_token_id, $user_id);
    $token_found = $stmt_token->fetch();
    $stmt_token->close();

    if (!$token_found) {
        $audit('reset_invalid_or_expired_token');
        $db->rollback();
        $db->close();
        header('Location: ' . ROOT_DIR . 'reset-password.php?error_key=' . urlencode('actions.password_reset.invalid_or_expired_token'));
        exit;
    }

    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt_update_password = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt_update_password->bind_param('si', $new_hash, $user_id);
    $stmt_update_password->execute();
    $stmt_update_password->close();

    // Invalidate this and all other active reset tokens for this user.
    $stmt_invalidate_tokens = $db->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL');
    $stmt_invalidate_tokens->bind_param('i', $user_id);
    $stmt_invalidate_tokens->execute();
    $stmt_invalidate_tokens->close();

    $db->commit();
    $db->close();

    $audit('reset_success', ['user_id' => (int)$user_id]);

    header('Location: ' . ROOT_DIR . 'login.php?reset=1');
    exit;
} catch (Exception $e) {
    $audit('reset_error');
    if (isset($db) && $db instanceof mysqli) {
        $db->rollback();
        $db->close();
    }

    header('Location: ' . ROOT_DIR . 'reset-password.php?error_key=' . urlencode('actions.password_reset.update_failed'));
    exit;
}
