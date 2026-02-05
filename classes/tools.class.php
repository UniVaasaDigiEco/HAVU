<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/user.class.php');
use Ramsey\Uuid\Uuid;
class Tools{
    /** Creata a database connection and return a mysqli object
     * @return mysqli
     */
    public static function getDb(): mysqli{
        try{
            $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $db->set_charset('utf8mb4');
            return $db;
        }
        catch (Exception $exception){
            throw new RuntimeException("Database connection failed: " . $exception->getMessage());
        }
    }

    /** Get a User object by public UUID from the database
     * @param $public_id
     * @return User
     * @throws Exception
     */
    public static function getUserWithPublicId($public_id): User{
        $db = self::getDb();
        $uuidBytes = $public_id->getBytes();
        $sql = "SELECT id FROM users WHERE public_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $uuidBytes);
        $stmt->execute();
        /** @var int $user_id */
        $stmt->bind_result($user_id);
        $stmt->store_result();
        if($stmt->num_rows === 0){
            throw new Exception("User not found");
        }
        $stmt->fetch();
        return new User($user_id);
    }

    /** Parse a datetime string into a DateTime object. Throw an exception if parsing fails or the string is null.
     * @param string|null $datetime
     * @return DateTime|null
     */
    public static function parseDateTime(?string $datetime): ?DateTime {
        if ($datetime === null){
            return null;
        }

        try {
            return new DateTime($datetime);
        } catch (Exception $e) {
            throw new RuntimeException("Failed to parse datetime: '$datetime': " . $e->getMessage());
        }
    }

    /** Parse a UUID from its byte representation into a usable string.
     * @param string $bytes
     * @return string
     */
    public static function parseUuidFromBytes(string $bytes): string {
        try {
            return Uuid::fromBytes($bytes)->toString();
        } catch (Exception $exception) {
            throw new RuntimeException("Failed to parse UUID from bytes: " . $exception->getMessage());
        }
    }
}