<?php
require_once(__DIR__ .'/../vendor/autoload.php');
require_once(__DIR__ .'/../classes/tools.class.php');
require_once(__DIR__ .'/../classes/node.class.php');
use Ramsey\Uuid\Uuid;

class Route{
    private int $id;
    private string $public_id;
    private bool $is_published;
    private ?DateTime $publication_date;
    private DateTime $created_at;
    private DateTime $updated_at;
    private string $created_by;
    private string $user_id;
    private string $title;
    private string $description;
    private int $gps_threshold;
    private int $allow_route_line;

    /** @var array<int, array{cross_id: int, node: Node, order_number: int}> */
    private array $nodes = [];

    public function __construct(int $id){
        if($id <= 0){
            throw new InvalidArgumentException("Invalid route ID");
        }

        $db = Tools::getDb();
        $sql = "SELECT public_id, is_published, publication_date, created_by, created_at, updated_at, user_id, title, description, gps_threshold, allow_route_line FROM routes WHERE id = ?";
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
             * @var int $gps_threshold
             * @var int $allow_route_line
             */
            $stmt->bind_result($public_id, $is_published, $publication_date, $created_by, $created_at, $updated_at, $user_id, $title, $description, $gps_threshold, $allow_route_line);
            $stmt->store_result();
            if($stmt->num_rows === 0){
                throw new Exception("Route not found");
            }
            $stmt->fetch();
            $this->id = $id;
            $this->is_published = (bool)$is_published;
            $this->public_id = Tools::parseUuidFromString($public_id);
            $this->publication_date = $publication_date ? Tools::parseDateTime($publication_date) : null;
            $this->created_by = $created_by;
            $this->created_at = Tools::parseDateTime($created_at);
            $this->updated_at = Tools::parseDateTime($updated_at);
            $this->user_id = $user_id;
            $this->title = $title;
            $this->description = $description;
            $this->gps_threshold = max(15, min(50, (int)($gps_threshold ?? 25)));
            $this->allow_route_line = (int)($allow_route_line ?? 0);
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
    public function getGpsThreshold(): int
    {
        return $this->gps_threshold;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * Convert route to array format
     * @return array
     */
    public function toArray(): array
    {
        $nodes_array = [];
        foreach ($this->nodes as $node_data) {
            $nodes_array[] = [
                'order_number' => $node_data['order_number'],
                'node' => $node_data['node']->toArray()
            ];
        }

        return [
            'public_id' => $this->public_id,
            'is_published' => $this->is_published,
            'publication_date' => $this->publication_date?->format('Y-m-d'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'created_by' => $this->created_by,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            'gps_threshold' => $this->gps_threshold,
            'allow_route_line' => $this->allow_route_line,
            'nodes' => $nodes_array
        ];
    }

    /**
     * Convert route to JSON string
     * @param int $options JSON encoding options (default: JSON_PRETTY_PRINT)
     * @return string
     */
    public function toJson(int $options = JSON_PRETTY_PRINT): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get route data in JavaScript-ready format
     * @return string JavaScript object literal
     */
    public function toJavaScript(): string
    {
        return $this->toJson(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    /**
     * Delete the route and its associated node references
     * @throws Exception if deletion fails
     */
    public function delete(): void{
        $db = Tools::getDb();
        $db->begin_transaction();
        try {
            // Delete node-route cross references
            $sql_cross = "DELETE FROM node_route_cross WHERE route_id = ? ORDER BY order_number";
            $stmt_cross = $db->prepare($sql_cross);
            $stmt_cross->bind_param('i', $this->id);
            if (!$stmt_cross->execute()) {
                throw new Exception('Failed to delete node-route references: ' . $stmt_cross->error);
            }
            $stmt_cross->close();

            // Delete the route
            $sql_route = "DELETE FROM routes WHERE id = ?";
            $stmt_route = $db->prepare($sql_route);
            $stmt_route->bind_param('i', $this->id);
            if (!$stmt_route->execute()) {
                throw new Exception('Failed to delete route: ' . $stmt_route->error);
            }
            $stmt_route->close();

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw new RuntimeException("Failed to delete route: " . $e->getMessage());
        } finally {
            $db->close();
        }
    }
}