<?php
require_once('../vendor/autoload.php');
require_once('../classes/tools.class.php');
require_once('../classes/security.class.php');
use Ramsey\Uuid\Uuid;

Security::initSession();

$route_public_id = $_POST['route_public_id'] ?? '';
$return_url = '../pages/admin/edit-route.php';

if (!empty($route_public_id)) {
    $return_url .= '?route_public_id=' . urlencode($route_public_id);
}

try {
    if (empty($_SESSION['user_public_id'])) {
        throw new Exception('User not authenticated');
    }

    if (empty($route_public_id) || empty($_POST['route_title']) || empty($_POST['publication_date']) || empty($_POST['nodes_data'])) {
        throw new Exception('Missing required fields');
    }

    $route_title = trim($_POST['route_title']);
    $route_description = trim($_POST['route_description'] ?? '');
    $created_by = $_SESSION['user_public_id'];

    $publication_date = DateTime::createFromFormat('Y-m-d', $_POST['publication_date']);
    if (!$publication_date) {
        throw new Exception('Invalid publication date format. Expected YYYY-MM-DD');
    }
    $formatted_publication_date = $publication_date->format('Y-m-d');

    $nodes_data = json_decode($_POST['nodes_data']);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON in nodes_data: ' . json_last_error_msg());
    }
    if (!is_array($nodes_data) || count($nodes_data) === 0) {
        throw new Exception('No nodes provided');
    }

    $db = Tools::getDb();
    $db->begin_transaction();

    try {
        // Ensure route exists and belongs to current user
        $route_lookup_sql = 'SELECT id FROM routes WHERE public_id = ? AND user_id = ? LIMIT 1';
        $route_lookup_stmt = $db->prepare($route_lookup_sql);
        if (!$route_lookup_stmt) {
            throw new Exception('Failed to prepare route lookup statement: ' . $db->error);
        }

        $route_lookup_stmt->bind_param('ss', $route_public_id, $created_by);
        if (!$route_lookup_stmt->execute()) {
            throw new Exception('Failed to lookup route: ' . $route_lookup_stmt->error);
        }

        $route_lookup_stmt->bind_result($route_id);
        $route_lookup_stmt->store_result();
        if ($route_lookup_stmt->num_rows === 0) {
            throw new Exception('Route not found or access denied');
        }
        $route_lookup_stmt->fetch();
        $route_lookup_stmt->close();

        // Update route details
        $route_update_sql = 'UPDATE routes SET title = ?, description = ?, publication_date = ?, is_published = ? WHERE id = ?';
        $route_update_stmt = $db->prepare($route_update_sql);
        if (!$route_update_stmt) {
            throw new Exception('Failed to prepare route update statement: ' . $db->error);
        }

        $is_published = 1;
        $route_update_stmt->bind_param('sssii', $route_title, $route_description, $formatted_publication_date, $is_published, $route_id);
        if (!$route_update_stmt->execute()) {
            throw new Exception('Failed to update route: ' . $route_update_stmt->error);
        }
        $route_update_stmt->close();

        // Collect old node ids so we can remove orphaned nodes later
        $old_node_ids = [];
        $old_nodes_sql = 'SELECT node_id FROM node_route_cross WHERE route_id = ?';
        $old_nodes_stmt = $db->prepare($old_nodes_sql);
        if (!$old_nodes_stmt) {
            throw new Exception('Failed to prepare old nodes statement: ' . $db->error);
        }

        $old_nodes_stmt->bind_param('i', $route_id);
        if (!$old_nodes_stmt->execute()) {
            throw new Exception('Failed to fetch old nodes: ' . $old_nodes_stmt->error);
        }

        $old_nodes_stmt->bind_result($old_node_id);
        while ($old_nodes_stmt->fetch()) {
            $old_node_ids[] = (int)$old_node_id;
        }
        $old_nodes_stmt->close();

        // Remove current node-route links
        $clear_cross_sql = 'DELETE FROM node_route_cross WHERE route_id = ?';
        $clear_cross_stmt = $db->prepare($clear_cross_sql);
        if (!$clear_cross_stmt) {
            throw new Exception('Failed to prepare cross clear statement: ' . $db->error);
        }

        $clear_cross_stmt->bind_param('i', $route_id);
        if (!$clear_cross_stmt->execute()) {
            throw new Exception('Failed to clear existing route nodes: ' . $clear_cross_stmt->error);
        }
        $clear_cross_stmt->close();

        // Prepare insert statements for fresh node set
        $node_sql = 'INSERT INTO nodes (public_id, is_published, publication_date, created_by, title, content, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $node_stmt = $db->prepare($node_sql);
        if (!$node_stmt) {
            throw new Exception('Failed to prepare node insert statement: ' . $db->error);
        }

        $cross_sql = 'INSERT INTO node_route_cross (node_id, route_id, order_number) VALUES (?, ?, ?)';
        $cross_stmt = $db->prepare($cross_sql);
        if (!$cross_stmt) {
            throw new Exception('Failed to prepare cross insert statement: ' . $db->error);
        }

        foreach ($nodes_data as $index => $node) {
            if (empty($node->title) || !isset($node->lat) || !isset($node->lng)) {
                throw new Exception('Invalid node data at index ' . $index);
            }

            $node_title = trim($node->title);
            $node_content = trim($node->content ?? '');
            $node_latitude = floatval($node->lat);
            $node_longitude = floatval($node->lng);

            if ($node_latitude < -90 || $node_latitude > 90 || $node_longitude < -180 || $node_longitude > 180) {
                throw new Exception('Invalid coordinates at node index ' . $index);
            }

            $node_public_id = Uuid::uuid4()->toString();
            $node_stmt->bind_param('sissssdd', $node_public_id, $is_published, $formatted_publication_date, $created_by, $node_title, $node_content, $node_latitude, $node_longitude);

            if (!$node_stmt->execute()) {
                throw new Exception('Failed to insert node at index ' . $index . ': ' . $node_stmt->error);
            }

            $node_insert_id = $node_stmt->insert_id;
            $order_number = $index;
            $cross_stmt->bind_param('iii', $node_insert_id, $route_id, $order_number);

            if (!$cross_stmt->execute()) {
                throw new Exception('Failed to insert node-route cross at index ' . $index . ': ' . $cross_stmt->error);
            }
        }

        $node_stmt->close();
        $cross_stmt->close();

        // Delete orphaned old nodes that are no longer attached to any route
        if (!empty($old_node_ids)) {
            $delete_orphan_sql = 'DELETE n FROM nodes n LEFT JOIN node_route_cross nrc ON n.id = nrc.node_id WHERE n.id = ? AND nrc.node_id IS NULL';
            $delete_orphan_stmt = $db->prepare($delete_orphan_sql);
            if (!$delete_orphan_stmt) {
                throw new Exception('Failed to prepare orphan cleanup statement: ' . $db->error);
            }

            foreach ($old_node_ids as $old_node_id_value) {
                $delete_orphan_stmt->bind_param('i', $old_node_id_value);
                if (!$delete_orphan_stmt->execute()) {
                    throw new Exception('Failed to cleanup old node ' . $old_node_id_value . ': ' . $delete_orphan_stmt->error);
                }
            }

            $delete_orphan_stmt->close();
        }

        $db->commit();

        $_SESSION['flash_messages'][] = [
            'type' => 'success',
            'code' => 2,
            'message' => 'Route updated successfully with ' . count($nodes_data) . ' nodes.',
            'data' => [
                'route_public_id' => $route_public_id,
                'nodes_count' => count($nodes_data)
            ]
        ];
    } catch (Exception $inner_exception) {
        $db->rollback();
        throw $inner_exception;
    } finally {
        $db->close();
    }
} catch (Exception $e) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 2,
        'message' => $e->getMessage()
    ];
}

header('Location: ' . $return_url);
exit;

