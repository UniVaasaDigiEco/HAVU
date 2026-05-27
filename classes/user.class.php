<?php
require_once(__DIR__ .'/../vendor/autoload.php');
require_once(__DIR__ .'/tools.class.php');
require_once(__DIR__ .'/route.class.php');
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class User{
    private int $id;
    private UuidInterface $public_id;
    private string $email;
    private string $password_hash;
    private string $full_name;
    private int $is_active;
    private ?DateTime $last_login;
    private DateTime $created_at;
    private DateTime $updated_at;
    private int $user_type;

    private array $created_routes;

    /** Create a User object by internal user ID
     * @param int $id Internal user ID
     * @throws Exception If user not found or other error occurs
     */
    public function __construct(int $id){
        if($id <= 0){
            throw new InvalidArgumentException("Invalid user ID");
        }

        $db = Tools::getDb();
        $sql = "SELECT id, public_id, email, password_hash, full_name, is_active, last_login, created_at, updated_at, user_type FROM users WHERE id = ?";
        $stmt = $db->prepare($sql);

        try{
            $stmt->bind_param("i", $id);
            $stmt->execute();
            /**
             * @var int $user_id
             * @var string $public_id
             * @var string $email
             * @var string $password_hash
             * @var string $full_name
             * @var int $is_active
             * @var string|null $last_login
             * @var string $created_at
             * @var string $updated_at
             * @var int $user_type
             */
            $stmt->bind_result($user_id, $public_id, $email, $password_hash, $full_name, $is_active, $last_login, $created_at, $updated_at, $user_type);
            $stmt->store_result();
            if($stmt->num_rows === 0){
                throw new Exception("User not found");
            }
            $stmt->fetch();

            $this->id = $user_id;
            try{
                $this->public_id = Uuid::fromString($public_id);
            }
            catch (Exception $exception){
                throw new RuntimeException("Failed to parse UUID from string: " . $exception->getMessage());
            }
            $this->email = $email;
            $this->password_hash = $password_hash;
            $this->full_name = $full_name;
            $this->is_active = $is_active;
            $this->last_login = Tools::parseDateTime($last_login);
            $this->created_at = Tools::parseDateTime($created_at);
            $this->updated_at = Tools::parseDateTime($updated_at);
            $this->user_type = $user_type;

            $this->created_routes = [];

            $route_sql = "SELECT id FROM routes WHERE user_id = ? ORDER BY title ASC";
            $route_stmt = $db->prepare($route_sql);
            $route_stmt->bind_param("s", $this->public_id);
            $route_stmt->execute();
            $route_stmt->bind_result($route_id);
            while($route_stmt->fetch()){
                try{
                    $route = new Route($route_id);
                    $this->created_routes[] = $route;
                }
                catch (Exception $e){
                    // Log error and continue loading other routes
                    error_log("Failed to load route with ID $route_id for user {$this->id}: " . $e->getMessage());
                }
            }
        } catch (Exception $e){
            throw new RuntimeException("Failed to create User object: " . $e->getMessage());
        } finally {
            $stmt->close();
            $db->close();
        }
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return UuidInterface
     */
    public function getPublicId(): UuidInterface
    {
        return $this->public_id;
    }
    /**
     * @return string
     */
    public function getEmail(): string{
        return $this->email;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return $this->full_name;
    }

    /**
     * @return int
     */
    public function getIsActive(): int
    {
        return $this->is_active;
    }

    /**
     * @return DateTime|null
     */
    public function getLastLogin(): ?DateTime
    {
        return $this->last_login;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updated_at;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->created_at;
    }

    /**
     * @return string
     */
    public function getPasswordHash(): string
    {
        return $this->password_hash;
    }

    public function updateLastLogin(DateTime $datetime): void {
        $db = Tools::getDb();
        $sql = "UPDATE users SET last_login = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $datetimeStr = $datetime->format('Y-m-d H:i:s');
        $stmt->bind_param('si', $datetimeStr, $this->id);
        $stmt->execute();
        $stmt->close();
        $db->close();
        $this->last_login = $datetime;
    }

    /**
     * @return int
     */
    public function getUserType(): int
    {
        return $this->user_type;
    }

    /**
     * @return array
     */
    public function getCreatedRoutes(): array
    {
        return $this->created_routes;
    }
}