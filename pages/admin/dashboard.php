<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HAVU - Gamification - Admin Dashboard</title>
    <link rel="stylesheet" href="../../css/bs-custom.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="admin-dashboard">
<div class="container-fluid vh-100 vw-100">
    <div class="d-flex flex-row py-3 h-100">
        <div id="menu" class="col-2 pe-2">
            <div id="menu-content" class="p-3 bg-primary-subtle rounded-3 shadow h-100">
                <h3 class="text-center"><i class="bi bi-gear-fill me-2"></i>Admin Dashboard</h3>
                <button class="btn btn-primary text-white w-100" id="show-route-management" data-target="#route-management">
                    <i class="bi bi-map-fill"></i> Manage routes
                </button>
            </div>
        </div>
        <div id="content" class="col-10">
            <div id="route-management" class="p-4 bg-secondary-subtle rounded-3 shadow h-100">
                <div class="mb-4">
                    <h3><i class="bi bi-map-fill me-2"></i>Route Management</h3>
                    <p class="lead">Here you can manage the routes for the game. Create new routes, edit existing ones, or remove routes that are no longer needed.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="new-route.php" class="btn btn-primary text-white" id="btn-newRoute">
                        <i class="bi bi-plus-circle-fill"></i> Add a new route
                    </a>
                    <a href="edit-route.php" class="btn btn-primary text-white" id="btn-editRoute">
                        <i class="bi bi-pencil-fill"></i> Edit an existing route
                    </a>
                    <a href="delete-route.php" class="btn btn-danger text-white" id="btn-deleteRoute">
                        <i class="bi bi-trash-fill"></i> Delete a route
                    </a>
                </div>
                <div class="mt-4">
                    <!-- Route list or management interface will go here -->
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
