<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');

Security::initSession();

function resolveSafeReturnTo(?string $candidate): ?string
{
    if (!is_string($candidate)) {
        return null;
    }

    $candidate = trim($candidate);
    if ($candidate === '' || preg_match('/[\r\n]/', $candidate)) {
        return null;
    }

    $parsed = parse_url($candidate);
    if ($parsed === false || isset($parsed['scheme']) || isset($parsed['host']) || isset($parsed['user']) || isset($parsed['pass'])) {
        return null;
    }

    $path = $parsed['path'] ?? '';
    if ($path === '' || !str_starts_with($path, ROOT_DIR)) {
        return null;
    }

    return $candidate;
}

function registerErrorUrl(string $errorKey, ?string $returnTo): string
{
    $url = '../register.php?error_key=' . urlencode($errorKey);
    if ($returnTo !== null) {
        $url .= '&return_to=' . urlencode($returnTo);
    }
    return $url;
}

// Redirect already-logged-in users
if (!empty($_SESSION['user_public_id'])) {
    header('Location: ' . ROOT_DIR . 'pages/routes.php');
    exit;
}

$return_to = resolveSafeReturnTo($_POST['return_to'] ?? null);

$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = $_POST['password'] ?? '';
$password2 = $_POST['password_confirm'] ?? '';

// Validate
if (empty($full_name) || mb_strlen($full_name) < 2) {
    header('Location: ' . registerErrorUrl('actions.register.name_required', $return_to));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . registerErrorUrl('actions.register.invalid_email', $return_to));
    exit;
}

if (mb_strlen($password) < 8) {
    header('Location: ' . registerErrorUrl('actions.register.password_length', $return_to));
    exit;
}

if ($password !== $password2) {
    header('Location: ' . registerErrorUrl('actions.register.password_mismatch', $return_to));
    exit;
}

// Check email uniqueness
$db = Tools::getDb();
$sql = "SELECT id FROM users WHERE email = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
$exists = $stmt->num_rows > 0;
$stmt->close();
$db->close();

if ($exists) {
    header('Location: ' . registerErrorUrl('actions.register.email_in_use', $return_to));
    exit;
}

try {
    Security::addUser($email, $password, $full_name, USER_TYPE_REGULAR);
} catch (Exception $e) {
    header('Location: ' . registerErrorUrl('actions.register.create_failed', $return_to));
    exit;
}

if (isset($_POST['request_admin'])) {
    $to      = 'support@havupeli.jansoftworks.fi';
    $subject = 'HAVU: Uusi käyttäjä pyytää reitinluontioikeuksia';
    $time    = (new DateTime())->format('d.m.Y H:i');
    $body    = "Hei,\n\n"
             . "Uusi käyttäjä on rekisteröitynyt HAVU-peliin ja pyytää oikeuksia reittien luomiseen.\n\n"
             . "Käyttäjätiedot:\n"
             . "  Nimi:               {$full_name}\n"
             . "  Sähköposti:         {$email}\n"
             . "  Rekisteröitymisaika: {$time}\n\n"
             . "Voit muuttaa käyttäjän tyypiksi admin ylläpitopaneelissa.\n\n"
             . "---\nHAVU-pelialusta";
    $headers = "From: noreply@havupeli.jansoftworks.fi\r\n"
             . "Reply-To: {$email}\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";
    mail($to, $subject, $body, $headers);
}

if ($return_to !== null) {
    try {
        $db = Tools::getDb();
        $sql = 'SELECT public_id FROM users WHERE email = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($public_id_string);
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->fetch();
            $user = Tools::getUserWithPublicId($public_id_string);

            $_SESSION['user_public_id'] = $user->getPublicId()->toString();
            $_SESSION['is_logged_in'] = true;
            $_SESSION['is_admin'] = false;
            $_SESSION['login_timestamp'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];

            $user->updateLastLogin(new DateTime());

            $stmt->close();
            $db->close();

            header('Location: ' . $return_to);
            exit;
        }

        $stmt->close();
        $db->close();
    } catch (Exception $e) {
        // Fallback to default post-registration flow below.
    }
}

header('Location: ../login.php?registered=1');
exit;
