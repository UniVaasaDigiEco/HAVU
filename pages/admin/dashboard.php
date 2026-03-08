<?php
require_once('../../classes/tools.class.php');
require_once('../../classes/security.class.php');
require_once('../../classes/message.class.php');

Security::initSession();

try {
    $user = Tools::getUserWithPublicId($_SESSION['user_public_id']);
} catch (Exception $e) {
    die("Error fetching user: " . $e->getMessage());
}

$routes = $user->getCreatedRoutes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HAVU - Gamification - Ylläpitäjän hallintapaneeli</title>
    <link rel="stylesheet" href="../../css/bs-custom.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="../../node_modules/jquery/dist/jquery.min.js"></script>
    <script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="admin-dashboard">
<div class="container-fluid vh-100 vw-100">
    <div class="d-flex flex-row py-3 h-100">
        <div id="menu" class="col-2 pe-2">
            <div id="menu-content" class="p-3 bg-primary-subtle rounded-3 shadow h-100">
                <h3 class="text-center"><i class="bi bi-gear-fill me-2"></i>Ylläpitäjän hallintapaneeli</h3>
                <button class="btn btn-primary text-white w-100" id="show-route-management" data-target="#route-management">
                    <i class="bi bi-map-fill"></i> Hallitse reittejä
                </button>
            </div>
        </div>
        <div id="dashboard-content" class="col-10">
            <div id="route-management" class="p-4 bg-secondary-subtle rounded-3 shadow h-100">
                <div id="header" class="mb-4">
                    <h3><i class="bi bi-map-fill me-2"></i>Reittien hallinta</h3>
                    <?php
                    echo Message::displayFlashMessages();
                    ?>
                    <!--<p class="lead">Here you can manage the routes for the game. Create new routes, edit existing ones, or remove routes that are no longer needed.</p>-->
                    <p class="lead">Tästä voit hallita reittejäsi. Luo uusia reittejä, muokkaa olemassa olevia, tai poista reittejä, joita ei enää tarvita.</p>
                    <p>
                        <a href="files/user_guide_FI.docx" download>Lataa ohjeet tästä</a>
                    </p>
                </div>
                <div id="route-management-controls" class="d-flex flex-wrap gap-2">
                    <a href="new-route.php" class="btn btn-primary text-white" id="btn-newRoute">
                        <i class="bi bi-plus-circle-fill"></i> Lisää uusi reitti
                    </a>
                    <a href="edit-route.php" class="btn btn-primary text-white" id="btn-editRoute">
                        <i class="bi bi-pencil-fill"></i> Muokkaa reittiä
                    </a>
                    <a href="delete-route.php" class="btn btn-danger text-white" id="btn-deleteRoute">
                        <i class="bi bi-trash-fill"></i> Poista reitti
                    </a>
                </div>
                <div id="route-testing" class="mt-4">
                    <h4><i class="bi bi-map-fill me-2"></i>Reittien testaaminen</h4>
                    <p class="lead">Valitse pudotusvalikosta reitti ja klikkaa "Pelaa" testataksesi reittiä.</p>
                    <div class="mb-3">
                        <label for="route-select" class="form-label">Valitse testattava reitti:</label>
                        <select class="form-select" id="route-select">
                            <option selected disabled>Valitse reitti</option>
                            <?php
                            if($routes){
                                foreach ($routes as $route){
                                    echo "<option value='{$route->getPublicId()}'>{$route->getTitle()}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <button type="button" id="btn-play" class="btn btn-primary text-white"><i class="bi bi-joystick"></i> Pelaa</button>
                    <script>
                        $('#btn-play').on('click', function(){
                            const selectedRoutePublicId = $('#route-select').val();
                            console.log(selectedRoutePublicId);
                            if(!selectedRoutePublicId){
                                alert("Please select a route to test.");
                                return;
                            }
                            window.location.href = `../game.php?route=${selectedRoutePublicId}`;
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
