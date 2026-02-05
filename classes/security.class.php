<?php
require_once(__DIR__ .'/tools.class.php');
require_once(__DIR__ .'/../vendor/autoload.php');
use Ramsey\Uuid\Uuid;

class Security{
    /** Add a new user to the database
     * @param $email
     * @param $password
     * @param $fullName
     * @return void
     */
    public static function addUser($email, $password, $fullName): void{
        $db = Tools::getDb();

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
    }
}