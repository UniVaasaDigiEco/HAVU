<?php
require_once(__DIR__ .'/tools.class.php');
require_once(__DIR__ .'/../vendor/autoload.php');
use Ramsey\Uuid\Uuid;

class Security{
    /**
     * @return array<int, string>
     */
    public static function getSystemAdminAllowlist(): array
    {
        if (!defined('SYSTEM_ADMIN_ALLOWLIST') || !is_array(SYSTEM_ADMIN_ALLOWLIST)) {
            return [];
        }

        $result = [];
        foreach (SYSTEM_ADMIN_ALLOWLIST as $item) {
            $trimmed = is_string($item) ? trim($item) : '';
            if ($trimmed !== '') {
                $result[] = $trimmed;
            }
        }
        return $result;
    }

    public static function isSystemAdminPublicId(string $public_id): bool
    {
        $target = trim(mb_strtolower($public_id));
        if ($target === '') {
            return false;
        }

        foreach (self::getSystemAdminAllowlist() as $allowed_public_id) {
            if ($target === mb_strtolower($allowed_public_id)) {
                return true;
            }
        }

        return false;
    }

    public static function isCurrentUserSystemAdmin(): bool
    {
        self::initSession();

        $public_id = $_SESSION['user_public_id'] ?? '';
        if (!is_string($public_id) || trim($public_id) === '') {
            return false;
        }

        return self::isSystemAdminPublicId($public_id);
    }

    public static function getActiveSystemAdminCount(): int
    {
        $allowlist = self::getSystemAdminAllowlist();
        if ($allowlist === []) {
            return 0;
        }

        $active_count = 0;
        $db = Tools::getDb();

        try {
            $stmt = $db->prepare('SELECT public_id FROM users WHERE is_active = 1');
            $stmt->execute();
            $stmt->bind_result($public_id);

            while ($stmt->fetch()) {
                if (is_string($public_id) && self::isSystemAdminPublicId($public_id)) {
                    $active_count++;
                }
            }

            $stmt->close();
            return $active_count;
        } finally {
            $db->close();
        }
    }

    public static function getCsrfToken(string $context = 'default'): string{
        self::initSession();

        if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }

        if (empty($_SESSION['csrf_tokens'][$context])) {
            $_SESSION['csrf_tokens'][$context] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_tokens'][$context];
    }

    public static function validateCsrfToken(string $token, string $context = 'default'): bool{
        self::initSession();

        if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
            return false;
        }

        $expected = $_SESSION['csrf_tokens'][$context] ?? '';
        if (!is_string($expected) || $expected === '' || $token === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }

    /** Add a new user to the database
     * @param $email
     * @param $password
     * @param $fullName
     * @param $userType
     * @return void
     */
    public static function addUser($email, $password, $fullName, $userType, bool $tosAccepted = false): void{
        try{
            $db = Tools::getDb();

            $public_id = Uuid::uuid4()->toString();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $tosValue = $tosAccepted ? 1 : 0;

            $sql = "INSERT INTO users (public_id, email, password_hash, full_name, user_type, tos_accepted) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);

            if(!$stmt){
                throw new RuntimeException("Failed to prepare statement: " . $db->error);
            }

            $stmt->bind_param("ssssii", $public_id, $email, $passwordHash, $fullName, $userType, $tosValue);

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
