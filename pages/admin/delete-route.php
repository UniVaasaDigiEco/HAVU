<?php
require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ .'/../../classes/tools.class.php');
require_once(__DIR__ .'/../../classes/security.class.php');
use Ramsey\Uuid\Uuid;
Security::initSession();

$user_public_id_string = $_SESSION['user_public_id'];

try {
    $user = Tools::getUserWithPublicId($user_public_id_string);
} catch (Exception $e) {
    die("Error fetching user: " . $e->getMessage());
}
$routes = $user->getCreatedRoutes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HAVU Gamification - Poista Reitti</title>
    <link rel="stylesheet" href="../../css/bs-custom.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body class="admin-dashboard">
<div class="container-fluid vh-100">
    <div class="row py-3 h-100 align-items-center justify-content-center">
        <div class="col-md-6 text-center">
            <div class="p-5 bg-white rounded-3 shadow">
                <i class="bi bi-trash-fill text-danger" style="font-size: 4rem;"></i>
                <h2 class="mt-4">Poista reitti</h2>
                <form action="../../actions/delete-route.php" method="post" class="mt-4">
                    <div class="mb-3">
                        <label for="route_select" class="form-label">Valitse poistettava reitti</label>
                        <select name="route_public_id" id="route_select" class="form-select">
                            <option value="">-- Valitse --</option>
                            <?php
                            foreach($routes as $route) {
                                $route_pubhlic_id = $route->getPublicId();
                                $route_title = $route->getTitle();
                                echo "<option value='$route_pubhlic_id'>$route_title</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-danger" onclick="confirm('Oletko varma, että haluat poistaa tämän reitin? Tätä toimintoa ei voi peruuttaa.')">
                        <i class="bi bi-trash-fill"></i> Poista valittu reitti
                    </button>
                </form>
                <a href="dashboard.php" class="btn btn-primary mt-3">
                    <i class="bi bi-arrow-left"></i> Taikaisin hallintapaneeliin
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
