<?php
require_once('../vendor/autoload.php');
require_once('../classes/tools.class.php');
require_once('../classes/security.class.php');
use Ramsey\Uuid\Uuid;

Security::initSession();

$route_public_id = $_POST['route_public_id'] ?? '';
$return_url = '../pages/admin/edit-route.php';

function routeFormException(string $key, array $params = []): Exception
{
    return new Exception(json_encode(['key' => $key, 'params' => $params], JSON_THROW_ON_ERROR));
}

if (!empty($route_public_id)) {
    $return_url .= '?route_public_id=' . urlencode($route_public_id);
}

try {
    if (empty($_SESSION['user_public_id'])) {
        throw routeFormException('actions.route_form.user_not_authenticated');
    }

    if (empty($route_public_id) || empty($_POST['route_title']) || empty($_POST['publication_date']) || empty($_POST['nodes_data'])) {
        throw routeFormException('actions.route_form.missing_required_fields');
    }

    $route_title = trim($_POST['route_title']);
    $route_description = trim($_POST['route_description'] ?? '');
    $created_by = $_SESSION['user_public_id'];

    $publication_date = DateTime::createFromFormat('Y-m-d', $_POST['publication_date']);
    if (!$publication_date) {
        throw routeFormException('actions.route_form.invalid_publication_date');
    }
    $formatted_publication_date = $publication_date->format('Y-m-d');

    $nodes_data = json_decode($_POST['nodes_data']);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw routeFormException('actions.route_form.invalid_nodes_data');
    }
    if (!is_array($nodes_data) || count($nodes_data) === 0) {
        throw routeFormException('actions.route_form.no_nodes');
    }

    $db = Tools::getDb();
    $db->begin_transaction();

    try {
        // Ensure route exists and belongs to current user
        $route_lookup_sql = 'SELECT id FROM routes WHERE public_id = ? AND user_id = ? LIMIT 1';
        $route_lookup_stmt = $db->prepare($route_lookup_sql);
        if (!$route_lookup_stmt) {
            throw routeFormException('actions.route_form.update_failed');
        }

        $route_lookup_stmt->bind_param('ss', $route_public_id, $created_by);
        if (!$route_lookup_stmt->execute()) {
            throw routeFormException('actions.route_form.update_failed');
        }

        $route_lookup_stmt->bind_result($route_id);
        $route_lookup_stmt->store_result();
        if ($route_lookup_stmt->num_rows === 0) {
            throw routeFormException('actions.route_form.route_not_found_or_denied');
        }
        $route_lookup_stmt->fetch();
        $route_lookup_stmt->close();

        // Update route details
        $route_update_sql = 'UPDATE routes SET title = ?, description = ?, publication_date = ?, is_published = ? WHERE id = ?';
        $route_update_stmt = $db->prepare($route_update_sql);
        if (!$route_update_stmt) {
            throw routeFormException('actions.route_form.update_failed');
        }

        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $route_update_stmt->bind_param('sssii', $route_title, $route_description, $formatted_publication_date, $is_published, $route_id);
        if (!$route_update_stmt->execute()) {
            throw routeFormException('actions.route_form.update_failed');
        }
        $route_update_stmt->close();

        // Collect old node ids so we can remove orphaned nodes later
        $old_node_ids = [];
        $old_nodes_sql = 'SELECT node_id FROM node_route_cross WHERE route_id = ?';
        $old_nodes_stmt = $db->prepare($old_nodes_sql);
        if (!$old_nodes_stmt) {
            throw routeFormException('actions.route_form.update_failed');
        }

        $old_nodes_stmt->bind_param('i', $route_id);
        if (!$old_nodes_stmt->execute()) {
            throw routeFormException('actions.route_form.update_failed');
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
            throw routeFormException('actions.route_form.update_failed');
        }

        $clear_cross_stmt->bind_param('i', $route_id);
        if (!$clear_cross_stmt->execute()) {
            throw routeFormException('actions.route_form.update_failed');
        }
        $clear_cross_stmt->close();

        // Prepare insert statements for fresh node set
        $node_sql = 'INSERT INTO nodes (public_id, is_published, publication_date, created_by, title, content, latitude, longitude, challenge_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $node_stmt = $db->prepare($node_sql);
        if (!$node_stmt) {
            throw routeFormException('actions.route_form.update_failed');
        }

        $cross_sql = 'INSERT INTO node_route_cross (node_id, route_id, order_number) VALUES (?, ?, ?)';
        $cross_stmt = $db->prepare($cross_sql);
        if (!$cross_stmt) {
            throw routeFormException('actions.route_form.update_failed');
        }

        foreach ($nodes_data as $index => $node) {
            if (empty($node->title) || !isset($node->lat) || !isset($node->lng)) {
                throw routeFormException('actions.route_form.invalid_node_data', ['index' => $index]);
            }

            $node_title = trim($node->title);
            $node_content = trim($node->content ?? '');
            $node_latitude = floatval($node->lat);
            $node_longitude = floatval($node->lng);
            $node_challenge_data = isset($node->challenge_data) ? json_encode($node->challenge_data) : null;

            if ($node_latitude < -90 || $node_latitude > 90 || $node_longitude < -180 || $node_longitude > 180) {
                throw routeFormException('actions.route_form.invalid_coordinates', ['index' => $index]);
            }

            $node_public_id = Uuid::uuid4()->toString();
            $node_stmt->bind_param('sissssdds', $node_public_id, $is_published, $formatted_publication_date, $created_by, $node_title, $node_content, $node_latitude, $node_longitude, $node_challenge_data);

            if (!$node_stmt->execute()) {
                throw routeFormException('actions.route_form.update_failed');
            }

            $node_insert_id = $node_stmt->insert_id;
            $order_number = $index;
            $cross_stmt->bind_param('iii', $node_insert_id, $route_id, $order_number);

            if (!$cross_stmt->execute()) {
                throw routeFormException('actions.route_form.update_failed');
            }
        }

        $node_stmt->close();
        $cross_stmt->close();

        // Delete orphaned old nodes that are no longer attached to any route
        if (!empty($old_node_ids)) {
            $delete_orphan_sql = 'DELETE n FROM nodes n LEFT JOIN node_route_cross nrc ON n.id = nrc.node_id WHERE n.id = ? AND nrc.node_id IS NULL';
            $delete_orphan_stmt = $db->prepare($delete_orphan_sql);
            if (!$delete_orphan_stmt) {
                throw routeFormException('actions.route_form.update_failed');
            }

            foreach ($old_node_ids as $old_node_id_value) {
                $delete_orphan_stmt->bind_param('i', $old_node_id_value);
                if (!$delete_orphan_stmt->execute()) {
                    throw routeFormException('actions.route_form.update_failed');
                }
            }

            $delete_orphan_stmt->close();
        }

        $db->commit();

        $_SESSION['flash_messages'][] = [
            'type' => 'success',
            'code' => 2,
            'message_key' => 'actions.route_form.update_success',
            'message_params' => ['count' => count($nodes_data)],
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
    $decoded_message = json_decode($e->getMessage(), true);

    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 2,
        'message_key' => is_array($decoded_message) ? ($decoded_message['key'] ?? 'actions.route_form.update_failed') : 'actions.route_form.update_failed',
        'message_params' => is_array($decoded_message) ? ($decoded_message['params'] ?? []) : []
    ];
}

header('Location: ' . $return_url);
exit;

