<?php
require_once('../vendor/autoload.php');
require_once('../classes/tools.class.php');
require_once('../classes/security.class.php');
require_once('../classes/route.class.php');
require_once('../classes/node.class.php');

use Ramsey\Uuid\Uuid;

Security::initSession();

$return_url = "pages/admin/edit-route.php";

try {
    // Validate required POST data
    if (empty($_POST['route_id']) || empty($_POST['route_title']) || empty($_POST['nodes_data'])) {
        throw new Exception('Missing required fields');
    }

    // Validate session user
    if (empty($_SESSION['user_public_id'])) {
        throw new Exception('User not authenticated');
    }

    $db = Tools::getDb();
    $route_public_id = $_POST['route_id'];
    $route_title = $_POST['route_title'];
    $route_description = $_POST['route_description'] ?? '';

    // Get the route to verify ownership
    $route = Tools::getRouteByPublicId($route_public_id);
    $user = Tools::getUserWithPublicId($_SESSION['user_public_id']);
    $user_id = $user->getId();

    if ($route->getUserId() !== $user_id) {
        throw new Exception('You do not have permission to edit this route');
    }

    // Parse and validate nodes data
    $nodes_data = json_decode($_POST['nodes_data']);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON in nodes_data: ' . json_last_error_msg());
    }
    if (!is_array($nodes_data) || count($nodes_data) === 0) {
        throw new Exception('No nodes provided');
    }

    $route_id = $route->getId();

    // Start transaction
    $db->begin_transaction();

    try {
        // Update route
        $sql = "UPDATE routes SET title = ?, description = ?, is_published = 0, updated_at = NOW() WHERE public_id = ?";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare update statement: ' . $db->error);
        }
        $stmt->bind_param("sss", $route_title, $route_description, $route_public_id);

        if (!$stmt->execute()) {
            throw new Exception('Failed to update route: ' . $stmt->error);
        }
        $stmt->close();

        // Delete existing node-route cross references
        $sql_delete_cross = "DELETE FROM node_route_cross WHERE route_id = ?";
        $stmt_delete_cross = $db->prepare($sql_delete_cross);
        if (!$stmt_delete_cross) {
            throw new Exception('Failed to prepare delete statement: ' . $db->error);
        }
        $stmt_delete_cross->bind_param('i', $route_id);
        if (!$stmt_delete_cross->execute()) {
            throw new Exception('Failed to delete old node references: ' . $stmt_delete_cross->error);
        }
        $stmt_delete_cross->close();

        // Prepare node insert statement (for new nodes)
        $node_sql = "INSERT INTO nodes (public_id, is_published, publication_date, created_by, title, content, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $node_stmt = $db->prepare($node_sql);
        if (!$node_stmt) {
            throw new Exception('Failed to prepare node insert statement: ' . $db->error);
        }

        // Prepare node update statement (for existing nodes)
        $node_update_sql = "UPDATE nodes SET title = ?, content = ?, updated_at = NOW() WHERE id = ?";
        $node_update_stmt = $db->prepare($node_update_sql);
        if (!$node_update_stmt) {
            throw new Exception('Failed to prepare node update statement: ' . $db->error);
        }

        // Prepare cross reference insert statement
        $cross_sql = "INSERT INTO node_route_cross (node_id, route_id, order_number) VALUES (?, ?, ?)";
        $cross_stmt = $db->prepare($cross_sql);
        if (!$cross_stmt) {
            throw new Exception('Failed to prepare cross reference statement: ' . $db->error);
        }

        // Keep updated routes and created nodes private by default.
        $is_published = 0;
        $publication_date = date('Y-m-d');
        $created_by = $_SESSION['user_public_id'];

        // Insert/Update nodes and cross references
        foreach ($nodes_data as $index => $node) {
            // Validate node data
            if (empty($node->title) || !isset($node->lat) || !isset($node->lng)) {
                throw new Exception("Invalid node data at index $index");
            }

            $node_title = $node->title;
            $node_content = $node->content ?? '';
            $node_latitude = floatval($node->lat);
            $node_longitude = floatval($node->lng);

            // Validate coordinates
            if ($node_latitude < -90 || $node_latitude > 90 || $node_longitude < -180 || $node_longitude > 180) {
                throw new Exception("Invalid coordinates at node index $index");
            }

            // Check if this is a new node (id is null) or existing node
            if ($node->id === null) {
                // Create new node
                $node_public_id = Uuid::uuid4()->toString();
                $node_stmt->bind_param('sissssdd', $node_public_id, $is_published, $publication_date, $created_by, $node_title, $node_content, $node_latitude, $node_longitude);

                if (!$node_stmt->execute()) {
                    throw new Exception("Failed to insert node at index $index: " . $node_stmt->error);
                }
                $node_insert_id = $node_stmt->insert_id;
            } else {
                // Update existing node
                $node_id = $node->id;
                $node_update_stmt->bind_param('ssi', $node_title, $node_content, $node_id);

                if (!$node_update_stmt->execute()) {
                    throw new Exception("Failed to update node at index $index: " . $node_update_stmt->error);
                }
                $node_insert_id = $node_id;
            }

            // Insert cross reference
            $order_number = $index;
            $cross_stmt->bind_param('iii', $node_insert_id, $route_id, $order_number);

            if (!$cross_stmt->execute()) {
                throw new Exception("Failed to insert node-route cross reference at index $index: " . $cross_stmt->error);
            }
        }

        // Close statements
        $node_stmt->close();
        $node_update_stmt->close();
        $cross_stmt->close();

        // Commit transaction
        $db->commit();

        // Set success flash message
        $_SESSION['flash_messages'][] = [
            'type' => 'success',
            'code' => 2,
            'message' => 'Route updated successfully with ' . count($nodes_data) . ' nodes.',
            'data' => [
                'route_id' => $route_id,
                'route_public_id' => $route_public_id,
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

header('Location: ../' . $return_url . '?route_id=' . urlencode($_POST['route_id'] ?? ''));
exit;
?>


