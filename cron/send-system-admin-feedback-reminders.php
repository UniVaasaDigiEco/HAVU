<?php
/**
 * System Admin Feedback Reminder Cronjob
 *
 * Runs daily (morning) and sends reminder emails to active allowlisted
 * system-admin users when there are unread feedback inbox messages.
 *
 * Setup example:
 * 0 8 * * * /usr/bin/php /path/to/HavuGamification/cron/send-system-admin-feedback-reminders.php
 */

require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');

function logLine(string $message): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
}

function resolveBaseUrl(): string
{
    // constants.php currently loads $env from .env; use it when available.
    if (isset($GLOBALS['env']) && is_array($GLOBALS['env'])) {
        $raw = $GLOBALS['env']['HOME_URL'] ?? '';
        if (is_string($raw) && preg_match('#^https?://#i', $raw)) {
            return rtrim($raw, '/');
        }
    }

    return 'https://havupeli.jansoftworks.fi';
}

try {
    $allowlist = Security::getSystemAdminAllowlist();
    if (empty($allowlist)) {
        logLine('No system-admin allowlist entries configured. Exiting.');
        exit(0);
    }

    $db = Tools::getDb();

    $stmt_unread = $db->prepare('SELECT COUNT(*) FROM system_admin_messages WHERE is_read = 0');
    if (!$stmt_unread) {
        throw new RuntimeException('Failed to prepare unread count query.');
    }

    $stmt_unread->execute();
    $stmt_unread->bind_result($unread_count);
    $stmt_unread->fetch();
    $stmt_unread->close();

    $unread_count = (int)$unread_count;
    if ($unread_count < 1) {
        $db->close();
        logLine('No unread feedback inbox messages. Exiting.');
        exit(0);
    }

    $placeholders = implode(',', array_fill(0, count($allowlist), '?'));
    $sql_recipients = 'SELECT public_id, email, full_name
                       FROM users
                       WHERE is_active = 1 AND public_id IN (' . $placeholders . ')';

    $stmt_recipients = $db->prepare($sql_recipients);
    if (!$stmt_recipients) {
        throw new RuntimeException('Failed to prepare recipients query.');
    }

    $types = str_repeat('s', count($allowlist));
    $stmt_recipients->bind_param($types, ...$allowlist);
    $stmt_recipients->execute();
    $stmt_recipients->bind_result($recipient_public_id, $recipient_email, $recipient_name);

    $recipients = [];
    while ($stmt_recipients->fetch()) {
        $email = is_string($recipient_email) ? trim($recipient_email) : '';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        $recipients[] = [
            'public_id' => (string)$recipient_public_id,
            'email' => $email,
            'name' => is_string($recipient_name) && trim($recipient_name) !== '' ? trim($recipient_name) : 'System admin',
        ];
    }
    $stmt_recipients->close();

    if (empty($recipients)) {
        $db->close();
        logLine('No active allowlisted users with valid email addresses. Exiting.');
        exit(0);
    }

    $stmt_recent = $db->prepare(
        'SELECT feedback_type, subject, created_at
         FROM system_admin_messages
         WHERE is_read = 0
         ORDER BY created_at DESC, id DESC
         LIMIT 5'
    );

    $recent_lines = '';
    if ($stmt_recent) {
        $stmt_recent->execute();
        $stmt_recent->bind_result($recent_type, $recent_subject, $recent_created_at);

        while ($stmt_recent->fetch()) {
            $type = is_string($recent_type) ? $recent_type : 'feedback';
            $subject = is_string($recent_subject) && trim($recent_subject) !== '' ? trim($recent_subject) : '(no subject)';
            $created = is_string($recent_created_at) ? $recent_created_at : '';
            $recent_lines .= '  - [' . $type . '] ' . $subject . ' (' . $created . ")\n";
        }

        $stmt_recent->close();
    }

    $db->close();

    $base_url = resolveBaseUrl();
    $inbox_url = rtrim($base_url, '/') . ROOT_DIR . 'system-admin/messages.php?filter=unread';

    $host = parse_url($base_url, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        $host = 'havupeli.jansoftworks.fi';
    }

    $subject = 'HAVU: ' . $unread_count . ' unread feedback inbox message(s)';
    $headers = "From: no-reply@{$host}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $sent = 0;
    $failed = 0;

    logLine('Sending reminders to ' . count($recipients) . ' recipient(s). Unread count: ' . $unread_count . '.');

    foreach ($recipients as $recipient) {
        $body = "Hi {$recipient['name']},\n\n";
        $body .= "There are currently {$unread_count} unread message(s) in the HAVU system-admin feedback inbox.\n\n";

        if ($recent_lines !== '') {
            $body .= "Latest unread items:\n";
            $body .= $recent_lines . "\n";
        }

        $body .= "Open inbox:\n";
        $body .= $inbox_url . "\n\n";
        $body .= "---\n";
        $body .= "HAVU Platform\n";
        $body .= "This is an automated reminder email.\n";

        $safe_recipient = str_replace(["\r", "\n"], '', $recipient['email']);
        $ok = @mail($safe_recipient, $subject, $body, $headers);

        if ($ok) {
            $sent++;
            logLine('OK: Reminder sent to ' . $recipient['email'] . ' (' . $recipient['public_id'] . ').');
        } else {
            $failed++;
            logLine('ERROR: Failed to send reminder to ' . $recipient['email'] . ' (' . $recipient['public_id'] . ').');
        }
    }

    logLine('Completed. Sent: ' . $sent . ', Failed: ' . $failed . '.');
    exit($failed > 0 ? 1 : 0);
} catch (Exception $e) {
    logLine('Cron failed: ' . $e->getMessage());
    exit(1);
}
