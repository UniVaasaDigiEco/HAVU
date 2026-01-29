<?php
require_once('../vendor/autoload.php');
require_once('tools.class.php');
require_once('node.class.php');
use Ramsey\Uuid\Uuid;

class Route{
    private int $id;
    private string $public_id;
    private bool $is_published;
    private ?DateTime $publication_date;
    private DateTime $created_at;
    private DateTime $updated_at;
    private int $created_by;
    private int $user_id;
    private string $title;
    private string $description;

    /** @var array<int, array{cross_id: int, node: Node, order_number: int}> */
    private array $nodes = [];

    public function __construct(int $id){
        if($id <= 0){
            throw new InvalidArgumentException("Invalid route ID");
        }

        $db = Tools::GetDB();
        $sql = "SELECT public_id, is_published, publication_date, created_by, created_at, updated_at, user_id, title, description FROM routes WHERE id = ?";
        $stmt = $db->prepare($sql);

        $sql_nodes = "SELECT id, node_id, order_number FROM node_route_cross WHERE route_id = ? ORDER BY order_number";
        $stmt_nodes = $db->prepare($sql_nodes);

        try{
            //Fetch route details
            $stmt->bind_param('i', $id);
            $stmt->execute();
            /**
             * @var string $public_id
             * @var int $is_published
             * @var string $publication_date
             * @var int $created_by
             * @var string $created_at
             * @var string $updated_at
             * @var int $user_id
             * @var string $title
             * @var string $description
             */
            $stmt->bind_result($public_id, $is_published, $publication_date, $created_by, $created_at, $updated_at, $user_id, $title, $description);
            $stmt->store_result();
            if($stmt->num_rows === 0){
                throw new Exception("Route not found");
            }
            $stmt->fetch();
            $this->id = $id;
            $this->is_published = (bool)$is_published;
            $this->public_id = Tools::parseUuidFromBytes($public_id);
            $this->publication_date = $publication_date ? Tools::parseDateTime($publication_date) : null;
            $this->created_by = $created_by;
            $this->created_at = Tools::parseDateTime($created_at);
            $this->updated_at = Tools::parseDateTime($updated_at);
            $this->user_id = $user_id;
            $this->title = $title;
            $this->description = $description;

            //Fetch associated nodes
            $stmt_nodes->bind_param('i', $id);
            $stmt_nodes->execute();
            /**
             * @var int $cross_id
             * @var int $node_id
             * @var int $order_number
             */
            $stmt_nodes->bind_result($cross_id, $node_id, $order_number);
            $stmt_nodes->store_result();
            if($stmt_nodes->num_rows > 0){
                while($stmt_nodes->fetch()){
                    $this->nodes[$order_number] = [
                        'cross_id' => $cross_id,
                        'node' => new Node($node_id),
                        'order_number' => $order_number
                    ];
                }
            }

        }
        catch (Exception $exception){
            throw new RuntimeException("Failed to create Route object: " . $exception->getMessage());
        }
        finally{
            $stmt->close();
            $stmt_nodes->close();
            $db->close();
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPublicId(): string
    {
        return $this->public_id;
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
     * @return int
     */
    public function getCreatedBy(): int
    {
        return $this->created_by;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function getIsPublished(): bool
    {
        return $this->is_published;
    }

    /**
     * @return array
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return DateTime|null
     */
    public function getPublicationDate(): ?DateTime
    {
        return $this->publication_date;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }
}