<?php
require_once('../vendor/autoload.php');
require_once('../classes/tools.class.php');
require_once('../classes/security.class.php');

var_dump($_POST);

$route_title = $_POST['route_title'];
$route_description = $_POST['route_description'];
$publication_date = DateTime::createFromFormat('Y-m-d', $_POST['publication_date']);

$route_sql = "INSERT INTO routes (title, public_id description, publication_date) VALUES (?, ?, ?)";

$nodes_data = json_decode($_POST['nodes_data']);
foreach($nodes_data as $node){
    var_dump($node);
    echo "<br>";
}