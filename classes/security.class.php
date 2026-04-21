<?php
require_once(__DIR__ .'/tools.class.php');
require_once(__DIR__ .'/../vendor/autoload.php');
use Ramsey\Uuid\Uuid;

class Security{
    /** Add a new user to the database
     * @param $email
     * @param $password
     * @param $fullName
     * @param $userType
     * @return void
     */
    public static function addUser($email, $password, $fullName, $userType): void{
        try{
            $db = Tools::getDb();

            $public_id = Uuid::uuid4()->toString();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (public_id, email, password_hash, full_name, user_type) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);

            if(!$stmt){
                throw new RuntimeException("Failed to prepare statement: " . $db->error);
            }

            $stmt->bind_param("ssssi", $public_id, $email, $passwordHash, $fullName, $userType);

            if(!$stmt->execute()){
                throw new RuntimeException("Failed to execute statement: " . $stmt->error);
            }

            $stmt->close();
        }
        catch(Exception $exception){
            throw new RuntimeException("Failed to add user: " . $exception->getMessage());
        }
    }

    public static function authenticateUser($public_id, $password): bool{
        try{
            $user = Tools::getUserWithPublicId($public_id);
        }
        catch (Exception $exception){
            throw new RuntimeException("Authentication failed: " . $exception->getMessage());
        }

        if(password_verify($password, $user->getPasswordHash())){
            return true;
        }
        else{
            throw new RuntimeException("Authentication failed: Invalid password");
        }
    }

    public static function initSession(): void{
        if(session_status() === PHP_SESSION_NONE){
            session_name(SESSION_NAME);
            session_start();
        }

        HavuLocale::init();
    }

    public static function logout(): void{
        self::initSession();

        // Clear all session variables
        $_SESSION = [];

        // Delete the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy the session
        session_destroy();
    }
}
