<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');

Security::initSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_DIR . 'forgot-password.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$csrf_token = trim($_POST['csrf_token'] ?? '');
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
$email_hash = hash('sha256', mb_strtolower($email));

$audit = function (string $event, array $data = []) use ($ip, $email_hash): void {
    $payload = array_merge(['event' => $event, 'ip' => $ip, 'email_hash' => $email_hash], $data);
    error_log('password_reset_audit ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
};

$redirect_with_generic_success = function (): void {
    header('Location: ' . ROOT_DIR . 'forgot-password.php?sent=1');
    exit;
};

if (!Security::validateCsrfToken($csrf_token, 'forgot_password_request')) {
    $audit('request_invalid_csrf');
    $redirect_with_generic_success();
}

try {
    $db = Tools::getDb();

    // Log every request attempt for lightweight abuse tracking.
    $stmt_log = $db->prepare('INSERT INTO password_reset_request_log (email_hash, requested_ip) VALUES (?, ?)');
    $stmt_log->bind_param('ss', $email_hash, $ip);
    $stmt_log->execute();
    $stmt_log->close();

    // Simple throttling: always return generic success response.
    $stmt_ip = $db->prepare('SELECT COUNT(*) FROM password_reset_request_log WHERE requested_ip = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)');
    $stmt_ip->bind_param('s', $ip);
    $stmt_ip->execute();
    $stmt_ip->bind_result($ip_count);
    $stmt_ip->fetch();
    $stmt_ip->close();

    $stmt_email = $db->prepare('SELECT COUNT(*) FROM password_reset_request_log WHERE email_hash = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)');
    $stmt_email->bind_param('s', $email_hash);
    $stmt_email->execute();
    $stmt_email->bind_result($email_count);
    $stmt_email->fetch();
    $stmt_email->close();

    if ($ip_count > 10 || $email_count > 5) {
        $audit('request_throttled', ['ip_count' => (int)$ip_count, 'email_count' => (int)$email_count]);
        $db->close();
        $redirect_with_generic_success();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $audit('request_invalid_email');
        $db->close();
        $redirect_with_generic_success();
    }

    $stmt_user = $db->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
    $stmt_user->bind_param('s', $email);
    $stmt_user->execute();
    $stmt_user->bind_result($user_id);
    $user_found = $stmt_user->fetch();
    $stmt_user->close();

    if (!$user_found) {
        $audit('request_user_not_found');
        $db->close();
        $redirect_with_generic_success();
    }

    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);

    $stmt_token = $db->prepare(
        'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, requested_ip, requested_user_agent)
         VALUES (?, ?, (NOW() + INTERVAL 60 MINUTE), ?, ?)'
    );
    $stmt_token->bind_param('isss', $user_id, $token_hash, $ip, $user_agent);
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

    // Keep response identical regardless of mail() result.
    @mail($email, $subject, $message, $headers);

    $audit('request_token_issued', ['user_id' => (int)$user_id]);

    $db->close();
} catch (Exception $e) {
    $audit('request_error');
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
}

$redirect_with_generic_success();
