<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');

Security::initSession();

header('Content-Type: application/json');

function jsonError(string $msg): never {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Invalid request method.');
}

$type     = trim($_POST['type']            ?? '');
$name     = trim($_POST['name']            ?? '');
$email    = trim($_POST['email']           ?? '');
$message  = trim($_POST['message']         ?? '');
$page_url = trim($_POST['page_url']        ?? '');
$token    = trim($_POST['recaptcha_token'] ?? '');

if (!in_array($type, ['contact', 'bug', 'feature'], true)) {
    jsonError('Virheellinen viestityyppi.');
}
if ($name === '' || mb_strlen($name) > 100) {
    jsonError('Nimi on pakollinen (max 100 merkkiä).');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Anna kelvollinen sähköpostiosoite.');
}
if ($message === '') {
    jsonError('Viesti on pakollinen.');
}
if ($token === '') {
    jsonError('reCAPTCHA-tarkistus epäonnistui.');
}

// Verify reCAPTCHA v3 via cURL
$ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'secret'   => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'],
    ]),
]);
$raw = curl_exec($ch);
curl_close($ch);

$rc = json_decode($raw, true);
if (!$rc || !($rc['success'] ?? false) || ($rc['score'] ?? 0) < 0.5) {
    jsonError('reCAPTCHA-tarkistus epäonnistui. Yritä uudelleen.');
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

// Save to DB
$db   = Tools::getDb();
$stmt = $db->prepare(
    "INSERT INTO feedback_submissions (type, name, email, message, user_id, page_url)
     VALUES (?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    jsonError('Palautteen tallentaminen epäonnistui.');
}
$stmt->bind_param('ssssis', $type, $name, $email, $message, $user_id, $page_url);
if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    jsonError('Palautteen tallentaminen epäonnistui.');
}
$stmt->close();
$db->close();

// Send email (plain text — do NOT htmlspecialchars the body)
$type_labels = ['contact' => 'Contact Request', 'bug' => 'Bug Report', 'feature' => 'Feature Suggestion'];
$type_label  = $type_labels[$type];
$time        = (new DateTime())->format('d.m.Y H:i');
$logged_str  = $user_id !== null ? "Yes (user_id: {$user_id})" : 'No';

$body = "Hi,\n\n"
      . "A new message was submitted via the HAVU feedback form.\n\n"
      . "Type:       {$type_label}\n"
      . "Name:       {$name}\n"
      . "Email:      {$email}\n"
      . "Page:       {$page_url}\n"
      . "Time:       {$time}\n"
      . "Logged in:  {$logged_str}\n\n"
      . "Message:\n"
      . "-----------\n"
      . "{$message}\n"
      . "-----------\n\n"
      . "---\nHAVU Platform";

// Strip newlines from Reply-To to prevent header injection
$safe_reply_to = str_replace(["\r", "\n"], '', $email);

$headers = "From: noreply@havupeli.jansoftworks.fi\r\n"
         . "Reply-To: {$safe_reply_to}\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n";

mail('support@havupeli.jansoftworks.fi', "HAVU: New {$type_label} submission", $body, $headers);

echo json_encode(['ok' => true]);
