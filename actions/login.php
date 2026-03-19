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

$db = Tools::getDb();

$sql = "SELECT public_id FROM users WHERE email = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $_POST['email']);
$stmt->execute();
/** @var string $public_id_string */
$stmt->bind_result($public_id_string);
$stmt->store_result();
if($stmt->num_rows === 0){
    die("Login failed: User not found");
}
$stmt->fetch();
try{
    $public_id = Uuid::fromString($public_id_string);
}
catch (Exception $exception){
    die("Login failed: Invalid user ID");
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

        if($user->getUserType() === USER_TYPE_ADMIN){
            $_SESSION['is_admin'] = true;
            $url = ROOT_DIR . "pages/admin/dashboard.php";
        }
        else{
            $_SESSION['is_admin'] = false;
            $url = ROOT_DIR . "pages/testGame.php";
        }

        header("Location: $url");
        die();
    }
}
catch (Exception $exception){
    $error_code = 1; // Invalid credentials
    $url = ROOT_DIR . "login.php?error=" . urlencode($error_code);
    header("Location: $url");
    die();
}
