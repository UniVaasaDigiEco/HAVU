<?php
/**
 * Login action
 *
 * Log the user in and store necessary session data
 */
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');
use Ramsey\Uuid\Uuid;

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

function loginErrorUrl(int $errorCode, ?string $returnTo): string
{
    $url = ROOT_DIR . 'login.php?error=' . urlencode((string)$errorCode);
    if ($returnTo !== null) {
        $url .= '&return_to=' . urlencode($returnTo);
    }
    return $url;
}

$return_to = resolveSafeReturnTo($_POST['return_to'] ?? null);

$db = Tools::getDb();

$sql = "SELECT public_id FROM users WHERE email = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $_POST['email']);
$stmt->execute();
/** @var string $public_id_string */
$stmt->bind_result($public_id_string);
$stmt->store_result();
if($stmt->num_rows === 0){
    $error_code = 1;
    header('Location: ' . loginErrorUrl($error_code, $return_to));
    die();
}
$stmt->fetch();
try{
    $public_id = Uuid::fromString($public_id_string);
}
catch (Exception $exception){
    $error_code = 1;
    header('Location: ' . loginErrorUrl($error_code, $return_to));
    die();
}
try{
    if(Security::authenticateUser($public_id, $_POST['password'])){
        Security::initSession();

        $user = Tools::getUserWithPublicId($public_id);
        $user->updateLastLogin(new DateTime());

        $_SESSION['user_public_id'] = $user->getPublicId()->toString();
        $_SESSION['is_logged_in'] = true;
        $_SESSION['login_timestamp'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['is_admin'] = ($user->getUserType() === USER_TYPE_ADMIN);

        if ($return_to !== null) {
            $url = $return_to;
        } elseif($user->getUserType() === USER_TYPE_ADMIN){
            $url = ROOT_DIR . "pages/admin/dashboard.php";
        }
        else{
            $url = ROOT_DIR . "pages/player/dashboard.php";
        }

        header("Location: $url");
        die();
    }
}
catch (Exception $exception){
    $error_code = 1; // Invalid credentials
    $url = loginErrorUrl($error_code, $return_to);
    header("Location: $url");
    die();
}
