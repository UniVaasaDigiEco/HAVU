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
            $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, DB_SOCKET);
            $db->set_charset('utf8mb4');
            return $db;
        }
        catch (Exception $exception){
            $message = $exception->getMessage();

            // On Linux/shared hosting, "localhost" may force a Unix socket connection.
            // If the socket path is unavailable, retry over TCP via 127.0.0.1.
            if (DB_HOST === 'localhost' && str_contains($message, 'No such file or directory')) {
                try {
                    $db = new mysqli('127.0.0.1', DB_USER, DB_PASS, DB_NAME, DB_PORT);
                    $db->set_charset('utf8mb4');
                    return $db;
                } catch (Exception $fallbackException) {
                    throw new RuntimeException("Database connection failed: " . $fallbackException->getMessage());
                }
            }

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
        $uuidBytes = $public_id;
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

    public static function parseUuidFromString(string $uuidString): string {
        try {
            return Uuid::fromString($uuidString)->toString();
        } catch (Exception $exception) {
            throw new RuntimeException("Failed to parse UUID from string: " . $exception->getMessage());
        }
    }

    /** Fetch public_id and title of all routes from the database and return them as an array of associative arrays with keys 'public_id_string' and 'title'.
     * @return array
     */
    public static function getAllRoutePublicId(): array {
        $db = self::getDb();

        $sql = "SELECT public_id, title FROM routes";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $stmt->bind_result($public_id, $title);
        $stmt->store_result();
        $routes = [];
        while($stmt->fetch()){
            $routes[] = [
                'public_id_string' => $public_id,
                'title' => $title
            ];
        }
        return $routes;
    }


    /** Fetch the route ID from the database based on the provided public_id. Return a Route object if found, otherwise throw an exception.
     * @param string $public_id
     * @return Route
     * @throws Exception
     */
    public static function getRouteByPublicId(string $public_id): Route {
        $db = self::getDb();
        $sql = "SELECT id FROM routes WHERE public_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $public_id);
        $stmt->execute();
        $stmt->bind_result($route_id);
        $stmt->store_result();
        if($stmt->num_rows === 0){
            throw new Exception("Route not found");
        }
        $stmt->fetch();
        return new Route($route_id);
    }

    /** Get the internal integer user ID from a public UUID string, without loading the full User object.
     * @param string $public_id
     * @return int
     * @throws Exception
     */
    public static function getUserIdByPublicId(string $public_id): int {
        $db = self::getDb();
        $sql = "SELECT id FROM users WHERE public_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $public_id);
        $stmt->execute();
        $stmt->bind_result($user_id);
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            throw new Exception("User not found");
        }
        $stmt->fetch();
        $stmt->close();
        $db->close();
        return $user_id;
    }

    /**
     * Load a paginated user list for system-admin management.
     *
     * @param string $search
     * @param string $status all|active|inactive
     * @param int $page
     * @param int $per_page
     * @return array{users: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
     */
    public static function getSystemAdminUserPage(string $search = '', string $status = 'all', int $page = 1, int $per_page = 25): array
    {
        $search = trim($search);
        $status = in_array($status, ['all', 'active', 'inactive'], true) ? $status : 'all';
        $page = max(1, $page);
        $per_page = max(1, min(100, $per_page));
        $offset = ($page - 1) * $per_page;

        $db = self::getDb();

        try {
            $status_clause = '';
            if ($status === 'active') {
                $status_clause = ' AND is_active = 1';
            } elseif ($status === 'inactive') {
                $status_clause = ' AND is_active = 0';
            }

            if ($search !== '') {
                $search_like = '%' . $search . '%';

                $count_sql = 'SELECT COUNT(*) FROM users WHERE (full_name LIKE ? OR email LIKE ?)' . $status_clause;
                $count_stmt = $db->prepare($count_sql);
                $count_stmt->bind_param('ss', $search_like, $search_like);
                $count_stmt->execute();
                $count_stmt->bind_result($total);
                $count_stmt->fetch();
                $count_stmt->close();

                $sql = 'SELECT id, public_id, email, full_name, is_active, user_type, last_login, created_at'
                    . ' FROM users WHERE (full_name LIKE ? OR email LIKE ?)' . $status_clause
                    . ' ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?';
                $stmt = $db->prepare($sql);
                $stmt->bind_param('ssii', $search_like, $search_like, $per_page, $offset);
            } else {
                $count_sql = 'SELECT COUNT(*) FROM users WHERE 1=1' . $status_clause;
                $count_stmt = $db->prepare($count_sql);
                $count_stmt->execute();
                $count_stmt->bind_result($total);
                $count_stmt->fetch();
                $count_stmt->close();

                $sql = 'SELECT id, public_id, email, full_name, is_active, user_type, last_login, created_at'
                    . ' FROM users WHERE 1=1' . $status_clause
                    . ' ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?';
                $stmt = $db->prepare($sql);
                $stmt->bind_param('ii', $per_page, $offset);
            }

            $stmt->execute();
            $stmt->bind_result($id, $public_id, $email, $full_name, $is_active, $user_type, $last_login, $created_at);

            $users = [];
            while ($stmt->fetch()) {
                $users[] = [
                    'id' => (int)$id,
                    'public_id' => (string)$public_id,
                    'email' => (string)$email,
                    'full_name' => (string)$full_name,
                    'is_active' => (int)$is_active,
                    'user_type' => (int)$user_type,
                    'last_login' => $last_login,
                    'created_at' => (string)$created_at,
                ];
            }
            $stmt->close();

            $total = (int)$total;
            $total_pages = max(1, (int)ceil($total / $per_page));

            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => $total_pages,
            ];
        } finally {
            $db->close();
        }
    }

    /**
     * @param string $public_id
     * @return array{id:int, public_id:string, email:string, full_name:string, is_active:int, user_type:int}
     * @throws Exception
     */
    public static function getSystemAdminUserByPublicId(string $public_id): array
    {
        $db = self::getDb();

        try {
            $sql = 'SELECT id, public_id, email, full_name, is_active, user_type FROM users WHERE public_id = ? LIMIT 1';
            $stmt = $db->prepare($sql);
            $stmt->bind_param('s', $public_id);
            $stmt->execute();
            $stmt->bind_result($id, $db_public_id, $email, $full_name, $is_active, $user_type);
            $stmt->store_result();

            if ($stmt->num_rows === 0) {
                $stmt->close();
                throw new Exception('User not found');
            }

            $stmt->fetch();
            $stmt->close();

            return [
                'id' => (int)$id,
                'public_id' => (string)$db_public_id,
                'email' => (string)$email,
                'full_name' => (string)$full_name,
                'is_active' => (int)$is_active,
                'user_type' => (int)$user_type,
            ];
        } finally {
            $db->close();
        }
    }

    /** Get all routes created by a specific user, ordered by title
     * @param int $user_id
     * @return array Array of associative arrays with 'id', 'public_id', and 'title' keys
     */
    public static function getRoutesByUserId(int $user_id): array {
        $db = self::getDb();
        $sql = "SELECT id, public_id, title FROM routes WHERE user_id = ? ORDER BY title ASC, id DESC";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($id, $public_id, $title);
        $stmt->store_result();
        $routes = [];
        while($stmt->fetch()){
            $routes[] = [
                'id' => $id,
                'public_id' => $public_id,
                'title' => $title
            ];
        }
        return $routes;
    }

    /**
     * Load anonymised statistics for all routes owned by a creator.
     *
     * @param string $creator_public_id
     * @return array{summary: array{total_routes: int, total_completions: int, total_nodes_collected: int}, routes: array<int, array{public_id: string, title: string, is_published: int, total_nodes: int, started_count: int, finished_count: int, avg_nodes_collected: float, completion_rate: float}>}
     */
    public static function getRouteStatisticsForCreator(string $creator_public_id): array {
        $db = self::getDb();

        try {
            $summary_sql = "
                SELECT
                    COUNT(r.id) AS total_routes,
                    COALESCE(SUM(route_completion_totals.completed_count), 0) AS total_completions,
                    COALESCE(SUM(route_visit_totals.collected_nodes), 0) AS total_nodes_collected
                FROM routes r
                LEFT JOIN (
                    SELECT route_id, COUNT(*) AS completed_count
                    FROM route_completions
                    GROUP BY route_id
                ) route_completion_totals ON route_completion_totals.route_id = r.id
                LEFT JOIN (
                    SELECT route_id, COUNT(*) AS collected_nodes
                    FROM node_visits
                    GROUP BY route_id
                ) route_visit_totals ON route_visit_totals.route_id = r.id
                WHERE r.user_id = ?
            ";

            $summary_stmt = $db->prepare($summary_sql);
            $summary_stmt->bind_param('s', $creator_public_id);
            $summary_stmt->execute();
            $summary_stmt->bind_result($total_routes, $total_completions, $total_nodes_collected);
            $summary_stmt->fetch();
            $summary_stmt->close();

            $route_sql = "
                SELECT
                    r.public_id,
                    r.title,
                    r.is_published,
                    COALESCE(node_totals.total_nodes, 0) AS total_nodes,
                    COALESCE(started_totals.started_count, 0) AS started_count,
                    COALESCE(finished_totals.finished_count, 0) AS finished_count,
                    COALESCE(avg_visit_totals.avg_nodes_collected, 0) AS avg_nodes_collected
                FROM routes r
                LEFT JOIN (
                    SELECT route_id, COUNT(*) AS total_nodes
                    FROM node_route_cross
                    GROUP BY route_id
                ) node_totals ON node_totals.route_id = r.id
                LEFT JOIN (
                    SELECT route_id, COUNT(DISTINCT user_id) AS started_count
                    FROM node_visits
                    GROUP BY route_id
                ) started_totals ON started_totals.route_id = r.id
                LEFT JOIN (
                    SELECT route_id, COUNT(DISTINCT user_id) AS finished_count
                    FROM route_completions
                    GROUP BY route_id
                ) finished_totals ON finished_totals.route_id = r.id
                LEFT JOIN (
                    SELECT route_id, AVG(visited_nodes) AS avg_nodes_collected
                    FROM (
                        SELECT route_id, user_id, COUNT(DISTINCT node_id) AS visited_nodes
                        FROM node_visits
                        GROUP BY route_id, user_id
                    ) route_user_visits
                    GROUP BY route_id
                ) avg_visit_totals ON avg_visit_totals.route_id = r.id
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC, r.id DESC
            ";

            $route_stmt = $db->prepare($route_sql);
            $route_stmt->bind_param('s', $creator_public_id);
            $route_stmt->execute();
            $route_stmt->bind_result($public_id, $title, $is_published, $total_nodes, $started_count, $finished_count, $avg_nodes_collected);

            $route_stats = [];

            while ($route_stmt->fetch()) {
                $started_count = (int)$started_count;
                $finished_count = (int)$finished_count;

                $route_stats[] = [
                    'public_id' => $public_id,
                    'title' => $title,
                    'is_published' => (int)$is_published,
                    'total_nodes' => (int)$total_nodes,
                    'started_count' => $started_count,
                    'finished_count' => $finished_count,
                    'avg_nodes_collected' => (float)$avg_nodes_collected,
                    'completion_rate' => $started_count > 0 ? ($finished_count / $started_count) * 100 : 0.0
                ];
            }

            $route_stmt->close();

            return [
                'summary' => [
                    'total_routes' => (int)$total_routes,
                    'total_completions' => (int)$total_completions,
                    'total_nodes_collected' => (int)$total_nodes_collected
                ],
                'routes' => $route_stats
            ];
        } finally {
            $db->close();
        }
    }
}
