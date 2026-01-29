<?php
require_once('../vendor/autoload.php');
require_once('tools.class.php');
use Ramsey\Uuid\Uuid;

class Node{
    private int $id;
    private string $public_id;
    private bool $is_published;
    private DateTime $publication_date;
    private int $created_by;
    private DateTime $created_at;
    private DateTime $updated_at;
    private string $title;
    private string $content;
    private float $latitude;
    private float $longitude;

    /** Create a Node object by internal node ID
     * @param int $id Internal node ID
     * @throws Exception If node not found or other error occurs
     */
    public function __construct(int $id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Invalid node ID");
        }
        $db = Tools::GetDB();
        $sql = "SELECT public_id, is_published, publication_date, created_by, created_at, updated_at, title, content, latitude, longitude FROM nodes WHERE id = ?";
        $stmt = $db->prepare($sql);

        try{
            $stmt->bind_param('i', $id);
            $stmt->execute();
            /**
             * @var string $public_id
             * @var int $is_published
             * @var string $publication_date
             * @var int $created_by
             * @var string $created_at
             * @var string $updated_at
             * @var string $title
             * @var string $content
             * @var float $latitude
             * @var float $longitude
             */
            $stmt->bind_result($public_id, $is_published, $publication_date, $created_by, $created_at, $updated_at, $title, $content, $latitude, $longitude);
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                throw new Exception("Node not found");
            }
            $stmt->fetch();
            $this->id = $id;
            $this->is_published = (bool)$is_published;
            try {
                $this->public_id = Uuid::fromBytes($public_id)->toString();
            } catch (Exception $exception) {
                throw new RuntimeException("Failed to parse UUID from bytes: " . $exception->getMessage());
            }
            $this->publication_date = Tools::parseDateTime($publication_date);
            $this->created_by = $created_by;
            $this->created_at = Tools::parseDateTime($created_at);
            $this->updated_at = Tools::parseDateTime($updated_at);
            $this->title = $title;
            $this->content = $content;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
        }
        finally{
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
     * @return string
     */
    public function getPublicId(): string {
        return $this->public_id;
    }

    /**
     * @return bool
     */
    public function getIsPublished(): bool
    {
        return $this->is_published;
    }

    /**
     * @return DateTime
     */
    public function getPublicationDate(): DateTime
    {
        return $this->publication_date;
    }

    /**
     * @return int
     */
    public function getCreatedBy(): int{
        return $this->created_by;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->created_at;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updated_at;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return float
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * @return float
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }
}
