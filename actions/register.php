<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');

Security::initSession();

// Redirect already-logged-in users
if (!empty($_SESSION['user_public_id'])) {
    header('Location: ' . ROOT_DIR . 'pages/routes.php');
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = $_POST['password'] ?? '';
$password2 = $_POST['password_confirm'] ?? '';

// Validate
if (empty($full_name) || mb_strlen($full_name) < 2) {
    header('Location: ../register.php?error=' . urlencode('Nimi on pakollinen (vähintään 2 merkkiä).'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../register.php?error=' . urlencode('Anna kelvollinen sähköpostiosoite.'));
    exit;
}

if (mb_strlen($password) < 8) {
    header('Location: ../register.php?error=' . urlencode('Salasanan on oltava vähintään 8 merkkiä pitkä.'));
    exit;
}

if ($password !== $password2) {
    header('Location: ../register.php?error=' . urlencode('Salasanat eivät täsmää.'));
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
    header('Location: ../register.php?error=' . urlencode('Sähköpostiosoite on jo käytössä.'));
    exit;
}

try {
    Security::addUser($email, $password, $full_name, USER_TYPE_REGULAR);
} catch (Exception $e) {
    header('Location: ../register.php?error=' . urlencode('Tunnuksen luominen epäonnistui. Yritä uudelleen.'));
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

header('Location: ../login.php?registered=1');
exit;
