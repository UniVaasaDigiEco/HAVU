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
    jsonError(t('actions.send_message.invalid_method'));
}

$route_id  = isset($_POST['route_id']) && $_POST['route_id'] !== '' ? (int)trim($_POST['route_id']) : null;
$title     = trim($_POST['title'] ?? '');
$content   = trim($_POST['content'] ?? '');
$token     = trim($_POST['recaptcha_token'] ?? '');

// Validate inputs
if ($title === '' || mb_strlen($title) > 255) {
    jsonError(t('actions.send_message.title_required'));
}

if ($content === '' || mb_strlen($content) > 5000) {
    jsonError(t('actions.send_message.content_required'));
}

if ($token === '') {
    jsonError(t('actions.send_message.recaptcha_failed'));
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
    jsonError(t('actions.send_message.recaptcha_retry'));
}

// Resolve route and find creator if route_id is provided
$recipient_user_id = null;
if ($route_id !== null) {
    $db = Tools::getDb();
    $stmt = $db->prepare("SELECT user_id FROM routes WHERE id = ? LIMIT 1");
    if (!$stmt) {
        $db->close();
        jsonError(t('actions.send_message.route_creator_not_found'));
    }

    $stmt->bind_param('i', $route_id);
    if (!$stmt->execute()) {
        $stmt->close();
        $db->close();
        jsonError(t('actions.send_message.route_creator_not_found'));
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        $db->close();
        jsonError(t('actions.send_message.route_not_found'));
    }

    $row = $result->fetch_assoc();
    $route_creator_public_id = trim((string)($row['user_id'] ?? ''));
    $stmt->close();

    if ($route_creator_public_id === '') {
        $db->close();
        jsonError(t('actions.send_message.route_creator_not_found'));
    }

    // Compare UUIDs as bytes to avoid collation mismatches between tables.
    $stmt = $db->prepare("SELECT id FROM users WHERE BINARY public_id = ? LIMIT 1");
    if (!$stmt) {
        $db->close();
        jsonError(t('actions.send_message.route_creator_not_found'));
    }

    $stmt->bind_param('s', $route_creator_public_id);
    if (!$stmt->execute()) {
        $stmt->close();
        $db->close();
        jsonError(t('actions.send_message.route_creator_not_found'));
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        $db->close();
        jsonError(t('actions.send_message.route_creator_not_found'));
    }

    $row = $result->fetch_assoc();
    $recipient_user_id = (int)$row['id'];
    $stmt->close();
    $db->close();
}

// If no route provided, check if user provided a recipient email or general admin message
// For now, general messages go to the first admin user (will be enhanced later)
if ($recipient_user_id === null) {
    $db = Tools::getDb();
    $stmt = $db->prepare("SELECT id FROM users WHERE user_type = 0 LIMIT 1");
    if (!$stmt) {
        $db->close();
        jsonError(t('actions.send_message.no_recipient'));
    }
    
    if (!$stmt->execute()) {
        $stmt->close();
        $db->close();
        jsonError(t('actions.send_message.no_recipient'));
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        $db->close();
        jsonError(t('actions.send_message.no_recipient'));
    }
    
    $row = $result->fetch_assoc();
    $recipient_user_id = $row['id'];
    $stmt->close();
    $db->close();
}

// Resolve sender's user ID from session if logged in
$sender_user_id = null;
if (!empty($_SESSION['user_public_id'])) {
    try {
        $sender_user_id = Tools::getUserIdByPublicId($_SESSION['user_public_id']);
    } catch (Exception $e) {
        // Non-fatal — submit anonymously
    }
}

// Save to database
try {
    $message = MessageCenter::create(
        $recipient_user_id,
        $sender_user_id,
        $route_id,
        $title,
        $content
    );
    
    // TODO: Send admin notification email if needed
    // For now, we rely on the Friday cronjob reminder email
    
    echo json_encode(['ok' => true, 'message_id' => $message->id]);
} catch (Exception $e) {
    jsonError(t('actions.send_message.save_failed'));
}
