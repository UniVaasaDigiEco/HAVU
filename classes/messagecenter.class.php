<?php
/**
 * MessageCenter Model
 * 
 * Represents an internal message sent from a player to a route creator.
 * Handles database operations for messages.
 */
class MessageCenter {
    public $id;
    public $recipient_user_id;
    public $sender_user_id;
    public $route_id;
    public $title;
    public $content;
    public $is_read;
    public $read_at;
    public $created_at;

    /**
     * Constructor: Load message by ID
     */
    public function __construct($id) {
        $this->id = $id;
        $this->load();
    }

    /**
     * Load message data from database
     */
    private function load() {
        $db = Tools::getDb();
        $stmt = $db->prepare(
            "SELECT recipient_user_id, sender_user_id, route_id, title, content, 
                    is_read, read_at, created_at 
             FROM messages 
             WHERE id = ?"
        );
        if (!$stmt) {
            $db->close();
            throw new Exception('Database error: ' . $db->error);
        }

        $stmt->bind_param('i', $this->id);
        if (!$stmt->execute()) {
            $stmt->close();
            $db->close();
            throw new Exception('Failed to fetch message');
        }

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $stmt->close();
            $db->close();
            throw new Exception('Message not found');
        }

        $row = $result->fetch_assoc();
        $this->recipient_user_id = $row['recipient_user_id'];
        $this->sender_user_id = $row['sender_user_id'];
        $this->route_id = $row['route_id'];
        $this->title = $row['title'];
        $this->content = $row['content'];
        $this->is_read = $row['is_read'];
        $this->read_at = $row['read_at'];
        $this->created_at = $row['created_at'];

        $stmt->close();
        $db->close();
    }

    /**
     * Create a new message
     */
    public static function create($recipient_user_id, $sender_user_id, $route_id, $title, $content) {
        $db = Tools::getDb();
        $stmt = $db->prepare(
            "INSERT INTO messages (recipient_user_id, sender_user_id, route_id, title, content)
             VALUES (?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            $db->close();
            throw new Exception('Database error: ' . $db->error);
        }

        $stmt->bind_param('iisis', $recipient_user_id, $sender_user_id, $route_id, $title, $content);
        if (!$stmt->execute()) {
            $stmt->close();
            $db->close();
            throw new Exception('Failed to create message');
        }

        $message_id = $db->insert_id;
        $stmt->close();
        $db->close();

        return new self($message_id);
    }

    /**
     * Mark message as read
     */
    public function markAsRead() {
        $db = Tools::getDb();
        $stmt = $db->prepare(
            "UPDATE messages SET is_read = 1, read_at = NOW() WHERE id = ?"
        );
        if (!$stmt) {
            $db->close();
            throw new Exception('Database error: ' . $db->error);
        }

        $stmt->bind_param('i', $this->id);
        if (!$stmt->execute()) {
            $stmt->close();
            $db->close();
            throw new Exception('Failed to mark message as read');
        }

        $this->is_read = 1;
        $this->read_at = date('Y-m-d H:i:s');
        $stmt->close();
        $db->close();
    }

    /**
     * Mark message as unread
     */
    public function markAsUnread() {
        $db = Tools::getDb();
        $stmt = $db->prepare(
            "UPDATE messages SET is_read = 0, read_at = NULL WHERE id = ?"
        );
        if (!$stmt) {
            $db->close();
            throw new Exception('Database error: ' . $db->error);
        }

        $stmt->bind_param('i', $this->id);
        if (!$stmt->execute()) {
            $stmt->close();
            $db->close();
            throw new Exception('Failed to mark message as unread');
        }

        $this->is_read = 0;
        $this->read_at = null;
        $stmt->close();
        $db->close();
    }

    /**
     * Delete message
     */
    public function delete() {
        $db = Tools::getDb();
        $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
        if (!$stmt) {
            $db->close();
            throw new Exception('Database error: ' . $db->error);
        }

        $stmt->bind_param('i', $this->id);
        if (!$stmt->execute()) {
            $stmt->close();
            $db->close();
            throw new Exception('Failed to delete message');
        }

        $stmt->close();
        $db->close();
    }

    /**
     * Get sender details
     */
    public function getSender() {
        if ($this->sender_user_id === null) {
            return null;
        }

        try {
            return new User($this->sender_user_id);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get route details if message is route-specific
     */
    public function getRoute() {
        if ($this->route_id === null) {
            return null;
        }

        try {
            return new Route($this->route_id);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get messages for a recipient (creator)
     * 
     * @param int $recipient_user_id The creator's user ID
     * @param array $options Filter options: 'is_read', 'route_id', 'sender_user_id', 'limit', 'offset'
     * @return array Array of message data
     */
    public static function getForRecipient($recipient_user_id, $options = []) {
        $db = Tools::getDb();
        
        $where = ['recipient_user_id = ?'];
        $types = 'i';
        $params = [$recipient_user_id];

        if (isset($options['is_read'])) {
            $where[] = 'is_read = ?';
            $types .= 'i';
            $params[] = $options['is_read'] ? 1 : 0;
        }

        if (isset($options['route_id'])) {
            $where[] = 'route_id = ?';
            $types .= 'i';
            $params[] = $options['route_id'];
        }

        if (isset($options['sender_user_id'])) {
            $where[] = 'sender_user_id = ?';
            $types .= 'i';
            $params[] = $options['sender_user_id'];
        }

        $sql = "SELECT * FROM messages WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";

        if (isset($options['limit'])) {
            $limit = (int)$options['limit'];
            $offset = isset($options['offset']) ? (int)$options['offset'] : 0;
            $sql .= " LIMIT {$offset}, {$limit}";
        }

        $stmt = $db->prepare($sql);
        if (!$stmt) {
            $db->close();
            throw new Exception('Database error: ' . $db->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            $db->close();
            throw new Exception('Failed to fetch messages');
        }

        $result = $stmt->get_result();
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }

        $stmt->close();
        $db->close();

        return $messages;
    }

    /**
     * Get unread message count for a recipient
     */
    public static function getUnreadCount($recipient_user_id) {
        $db = Tools::getDb();
        $stmt = $db->prepare(
            "SELECT COUNT(*) as cnt FROM messages WHERE recipient_user_id = ? AND is_read = 0"
        );
        if (!$stmt) {
            $db->close();
            throw new Exception('Database error: ' . $db->error);
        }

        $stmt->bind_param('i', $recipient_user_id);
        if (!$stmt->execute()) {
            $stmt->close();
            $db->close();
            throw new Exception('Failed to count unread messages');
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];

        $stmt->close();
        $db->close();

        return $count;
    }

    /**
     * Get all users with unread messages
     * Useful for cronjob to identify who needs a reminder email
     */
    public static function getUsersWithUnreadMessages() {
        $db = Tools::getDb();
        $sql = "SELECT DISTINCT recipient_user_id, COUNT(*) as unread_count
                FROM messages
                WHERE is_read = 0
                GROUP BY recipient_user_id";

        $stmt = $db->prepare($sql);
        if (!$stmt) {
            $db->close();
            throw new Exception('Database error: ' . $db->error);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            $db->close();
            throw new Exception('Failed to fetch users with unread messages');
        }

        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        $stmt->close();
        $db->close();

        return $users;
    }
}
