<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');
require_once(__DIR__ . '/../classes/system_admin_messagecenter.class.php');

Security::initSession();

header('Content-Type: application/json');

function jsonError(string $msg): never {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError(t('actions.submit_feedback.invalid_method'));
}

$type     = trim($_POST['type']            ?? '');
$name     = trim($_POST['name']            ?? '');
$email    = trim($_POST['email']           ?? '');
$message  = trim($_POST['message']         ?? '');
$page_url = trim($_POST['page_url']        ?? '');
if (mb_strlen($page_url) > 500) {
    $page_url = mb_substr($page_url, 0, 500);
}
$token    = trim($_POST['recaptcha_token'] ?? '');

if (!in_array($type, ['contact', 'bug', 'feature'], true)) {
    jsonError(t('actions.submit_feedback.invalid_type'));
}
if ($name === '' || mb_strlen($name) > 100) {
    jsonError(t('actions.submit_feedback.name_required'));
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError(t('actions.submit_feedback.invalid_email'));
}
if ($message === '' || mb_strlen($message) > 5000) {
    jsonError(t('actions.submit_feedback.message_required'));
}
if ($token === '') {
    jsonError(t('actions.submit_feedback.recaptcha_failed'));
}

// Verify reCAPTCHA v3 via cURL
$ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'secret'   => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]),
    CURLOPT_TIMEOUT        => 5,
    CURLOPT_CONNECTTIMEOUT => 3,
]);
$raw = curl_exec($ch);
curl_close($ch);

$rc = json_decode($raw, true);
if (!$rc || !($rc['success'] ?? false) || ($rc['score'] ?? 0) < 0.5) {
    jsonError(t('actions.submit_feedback.recaptcha_retry'));
}

// Resolve internal user_id from session if logged in
$user_id = null;
if (!empty($_SESSION['user_public_id'])) {
    try {
        $user_id = Tools::getUserIdByPublicId($_SESSION['user_public_id']);
    } catch (Exception $e) {
        // Non-fatal — submit anonymously
    }
}

// Save feedback submission
try {
    $db   = Tools::getDb();
    $stmt = $db->prepare(
        "INSERT INTO feedback_submissions (type, name, email, message, user_id, page_url)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        $db->close();
        jsonError(t('actions.submit_feedback.save_failed'));
    }
    $stmt->bind_param('ssssis', $type, $name, $email, $message, $user_id, $page_url);
    if (!$stmt->execute()) {
        $stmt->close();
        $db->close();
        jsonError(t('actions.submit_feedback.save_failed'));
    }
    $stmt->close();
    $db->close();
} catch (Exception $e) {
    jsonError(t('actions.submit_feedback.save_failed'));
}

// Save to system-admin inbox (shared view for allowlisted admins)
try {
    $type_labels = [
        'contact' => 'Contact Request',
        'bug' => 'Bug Report',
        'feature' => 'Feature Suggestion',
    ];
    $type_label = $type_labels[$type] ?? 'Feedback';
    $subject_preview = trim(preg_replace('/\s+/', ' ', $message));
    if ($subject_preview === '') {
        $subject_preview = 'No preview';
    }
    $subject = 'Feedback: ' . $type_label . ' - ' . mb_substr($subject_preview, 0, 80);

    SystemAdminMessageCenter::create(
        $user_id,
        $name,
        $email,
        $type,
        $subject,
        $message,
        $page_url
    );
} catch (Exception $e) {
    jsonError(t('actions.submit_feedback.save_failed'));
}

echo json_encode(['ok' => true]);
