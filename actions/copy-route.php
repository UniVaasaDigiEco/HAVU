<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');

use Ramsey\Uuid\Uuid;

Security::initSession();

if (empty($_SESSION['user_public_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ' . ROOT_DIR . 'login.php');
    exit;
}

$source_route_public_id = trim($_POST['route_public_id'] ?? '');
$new_route_title = trim($_POST['route_title'] ?? '');

if ($source_route_public_id === '' || $new_route_title === '') {
    $_SESSION['flash_messages'][] = ['type' => 'error', 'code' => 0, 'message_key' => 'actions.copy_route.missing_required_fields'];
    header('Location: ' . ROOT_DIR . 'pages/admin/dashboard.php');
    exit;
}

$db = Tools::getDb();

try {
    $route_stmt = $db->prepare('SELECT id, user_id, description, gps_threshold FROM routes WHERE public_id = ? LIMIT 1');
    if (!$route_stmt) {
        throw new RuntimeException('Failed to prepare route query.');
    }

    $route_stmt->bind_param('s', $source_route_public_id);
    $route_stmt->execute();
    $route_stmt->bind_result($source_route_id, $source_route_user_id, $source_route_description, $source_gps_threshold);

    if (!$route_stmt->fetch()) {
        $route_stmt->close();
        $_SESSION['flash_messages'][] = ['type' => 'error', 'code' => 0, 'message_key' => 'actions.copy_route.not_found'];
        header('Location: ' . ROOT_DIR . 'pages/admin/dashboard.php');
        exit;
    }
    $route_stmt->close();

    if ($source_route_user_id !== $_SESSION['user_public_id']) {
        $_SESSION['flash_messages'][] = ['type' => 'error', 'code' => 0, 'message_key' => 'actions.copy_route.forbidden'];
        header('Location: ' . ROOT_DIR . 'pages/admin/dashboard.php');
        exit;
    }

    $nodes_stmt = $db->prepare(
        'SELECT n.title, n.content, n.latitude, n.longitude, n.challenge_data, nrc.order_number
         FROM node_route_cross nrc
         INNER JOIN nodes n ON n.id = nrc.node_id
         WHERE nrc.route_id = ?
         ORDER BY nrc.order_number ASC'
    );

    if (!$nodes_stmt) {
        throw new RuntimeException('Failed to prepare node query.');
    }

    $nodes_stmt->bind_param('i', $source_route_id);
    $nodes_stmt->execute();
    $nodes_result = $nodes_stmt->get_result();

    $source_nodes = [];
    while ($row = $nodes_result->fetch_assoc()) {
        $source_nodes[] = $row;
    }
    $nodes_stmt->close();

    $db->begin_transaction();

    try {
        $new_route_public_id = Uuid::uuid4()->toString();
        $new_is_published = 0;
        $new_publication_date = date('Y-m-d');
        $creator_public_id = $_SESSION['user_public_id'];

        $insert_route_stmt = $db->prepare(
            'INSERT INTO routes (public_id, is_published, publication_date, created_by, user_id, title, description, gps_threshold)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        if (!$insert_route_stmt) {
            throw new RuntimeException('Failed to prepare route insert query.');
        }

        $insert_route_stmt->bind_param(
            'sisssssi',
            $new_route_public_id,
            $new_is_published,
            $new_publication_date,
            $creator_public_id,
            $creator_public_id,
            $new_route_title,
            $source_route_description,
            $source_gps_threshold
        );

        if (!$insert_route_stmt->execute()) {
            throw new RuntimeException('Failed to insert route copy.');
        }

        $new_route_id = $insert_route_stmt->insert_id;
        $insert_route_stmt->close();

        $insert_node_stmt = $db->prepare(
            'INSERT INTO nodes (public_id, is_published, publication_date, created_by, title, content, latitude, longitude, challenge_data)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        if (!$insert_node_stmt) {
            throw new RuntimeException('Failed to prepare node insert query.');
        }

        $insert_cross_stmt = $db->prepare(
            'INSERT INTO node_route_cross (node_id, route_id, order_number) VALUES (?, ?, ?)'
        );

        if (!$insert_cross_stmt) {
            throw new RuntimeException('Failed to prepare route-node link insert query.');
        }

        foreach ($source_nodes as $source_node) {
            $new_node_public_id = Uuid::uuid4()->toString();
            $node_title = (string)($source_node['title'] ?? '');
            $node_content = $source_node['content'];
            $node_latitude = (float)$source_node['latitude'];
            $node_longitude = (float)$source_node['longitude'];
            $node_challenge_data = $source_node['challenge_data'];
            $order_number = (int)$source_node['order_number'];

            $insert_node_stmt->bind_param(
                'sissssdds',
                $new_node_public_id,
                $new_is_published,
                $new_publication_date,
                $creator_public_id,
                $node_title,
                $node_content,
                $node_latitude,
                $node_longitude,
                $node_challenge_data
            );

            if (!$insert_node_stmt->execute()) {
                throw new RuntimeException('Failed to insert copied checkpoint.');
            }

            $new_node_id = $insert_node_stmt->insert_id;

            $insert_cross_stmt->bind_param('iii', $new_node_id, $new_route_id, $order_number);
            if (!$insert_cross_stmt->execute()) {
                throw new RuntimeException('Failed to link copied checkpoint to copied route.');
            }
        }

        $insert_node_stmt->close();
        $insert_cross_stmt->close();

        $db->commit();

        $_SESSION['flash_messages'][] = [
            'type' => 'success',
            'code' => 0,
            'message_key' => 'actions.copy_route.success',
            'message_params' => ['count' => count($source_nodes)],
        ];
    } catch (Throwable $copy_exception) {
        $db->rollback();
        throw $copy_exception;
    }
} catch (Throwable $e) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message_key' => 'actions.copy_route.failed',
    ];
} finally {
    $db->close();
}

header('Location: ' . ROOT_DIR . 'pages/admin/dashboard.php');
exit;
