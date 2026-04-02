<?php
require_once('../vendor/autoload.php');
require_once('../classes/tools.class.php');
require_once('../classes/security.class.php');
use Ramsey\Uuid\Uuid;

Security::initSession();

header('Content-Type: application/json');

$return_url = "pages/admin/dashboard.php";

try {
    // Validate required POST data
    if (empty($_POST['route_title']) || empty($_POST['route_description']) || empty($_POST['publication_date']) || empty($_POST['nodes_data'])) {
        throw new Exception('Missing required fields');
    }

    // Validate session user
    if (empty($_SESSION['user_public_id'])) {
        throw new Exception('User not authenticated');
    }

    $db = Tools::getDb();

    // Validate and parse publication date
    $publication_date = DateTime::createFromFormat('Y-m-d', $_POST['publication_date']);
    if (!$publication_date) {
        throw new Exception('Invalid publication date format. Expected YYYY-MM-DD');
    }
    $formatted_publication_date = $publication_date->format('Y-m-d');

    // Parse and validate nodes data
    $nodes_data = json_decode($_POST['nodes_data']);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON in nodes_data: ' . json_last_error_msg());
    }
    if (!is_array($nodes_data) || count($nodes_data) === 0) {
        throw new Exception('No nodes provided');
    }

    // Prepare route data
    $public_id = Uuid::uuid4()->toString();
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $created_by = $_SESSION['user_public_id'];
    $title = $_POST['route_title'];
    $description = $_POST['route_description'];

    // Start transaction
    $db->begin_transaction();

    try {
        // Insert route
        $sql = "INSERT INTO routes (public_id, is_published, publication_date, created_by, user_id, title, description) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare route statement: ' . $db->error);
        }
        $stmt->bind_param("sisssss", $public_id, $is_published, $formatted_publication_date, $created_by, $created_by, $title, $description);

        if (!$stmt->execute()) {
            throw new Exception('Failed to insert route: ' . $stmt->error);
        }
        $route_insert_id = $stmt->insert_id;
        $stmt->close();

        // Prepare node statement
        $node_sql = "INSERT INTO nodes (public_id, is_published, publication_date, created_by, title, content, latitude, longitude, challenge_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        // bind_param types for nodes: s:public_id, i:is_published, s:publication_date, s:created_by, s:title, s:content, d:latitude, d:longitude, s:challenge_data
        $node_stmt = $db->prepare($node_sql);
        if (!$node_stmt) {
            throw new Exception('Failed to prepare node statement: ' . $db->error);
        }

        // Prepare cross reference statement
        $cross_sql = "INSERT INTO node_route_cross (node_id, route_id, order_number) VALUES (?, ?, ?)";
        $cross_stmt = $db->prepare($cross_sql);
        if (!$cross_stmt) {
            throw new Exception('Failed to prepare cross reference statement: ' . $db->error);
        }

        // Insert nodes and cross references
        foreach ($nodes_data as $index => $node) {
            // Validate node data
            if (empty($node->title) || !isset($node->lat) || !isset($node->lng)) {
                throw new Exception("Invalid node data at index $index");
            }

            $node_public_id = Uuid::uuid4()->toString();
            $node_title = $node->title;
            $node_content = $node->content ?? '';
            $node_latitude = floatval($node->lat);
            $node_longitude = floatval($node->lng);
            $node_challenge_data = isset($node->challenge_data) ? json_encode($node->challenge_data) : null;

            // Validate coordinates
            if ($node_latitude < -90 || $node_latitude > 90 || $node_longitude < -180 || $node_longitude > 180) {
                throw new Exception("Invalid coordinates at node index $index");
            }

            $node_stmt->bind_param('sissssdds', $node_public_id, $is_published, $formatted_publication_date, $created_by, $node_title, $node_content, $node_latitude, $node_longitude, $node_challenge_data);

            if (!$node_stmt->execute()) {
                throw new Exception("Failed to insert node at index $index: " . $node_stmt->error);
            }
            $node_insert_id = $node_stmt->insert_id;

            // Insert cross reference (fixed bug: was $route_id twice, should be $node_insert_id first)
            $order_number = $index;
            $cross_stmt->bind_param('iii', $node_insert_id, $route_insert_id, $order_number);

            if (!$cross_stmt->execute()) {
                throw new Exception("Failed to insert node-route cross reference at index $index: " . $cross_stmt->error);
            }
        }

        // Close statements
        $node_stmt->close();
        $cross_stmt->close();

        // Commit transaction
        $db->commit();

        // Set success flash message
        $_SESSION['flash_messages'][] = [
            'type' => 'success',
            'code' => 2,
            'message' => 'Route created successfully with ' . count($nodes_data) . ' nodes.',
            'data' => [
                'route_id' => $route_insert_id,
                'route_public_id' => $public_id,
                'nodes_count' => count($nodes_data)
            ]
        ];

    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    // Set error flash message
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 2,
        'message' => $e->getMessage()
    ];
}

header('Location: ../' . $return_url);
exit;
