<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../classes/route.class.php');
require_once(__DIR__ ."/../classes/tools.class.php");

$route_public_id = $_POST['route_public_id'];

try {
    $route = Tools::getRouteByPublicId($route_public_id);
} catch (Exception $e) {
    die("Error fetching route: " . $e->getMessage());
}

try {
    $route->delete();
    header("Location: ../pages/admin/dashboard.php?message=Route deleted successfully");
} catch (Exception $e) {
    die("Error deleting route: " . $e->getMessage());
}

header("Location: ../pages/admin/dashboard.php?message=Route deleted successfully");