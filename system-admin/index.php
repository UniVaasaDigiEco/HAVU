<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/security.class.php');
require_once(__DIR__ . '/../classes/message.class.php');

Security::initSession();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAVU System Administration</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="stylesheet" href="../css/bs-custom.css">
    <link rel="stylesheet" href="../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="admin-dashboard has-site-footer">
<div class="container-fluid py-4 mx-2">
    <div class="admin-page-content">
        <div class="admin-page-hero p-4 p-lg-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-7">
                    <span class="badge bg-dark text-white mb-3 px-3 py-2">Internal use only</span>
                    <h3 class="mb-3"><i class="bi bi-shield-lock-fill me-2"></i>System Administration</h3>
                    <p class="lead mb-4">
                        This area is reserved for your team. It will be used to manage users, routes, permissions,
                        and other system-level actions that regular admins and players should never access.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary text-white btn-lg" href="./dashboard.php">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Open dashboard
                        </a>
                        <a class="btn btn-outline-secondary btn-lg" href="../pages/admin/dashboard.php">
                            <i class="bi bi-gear-fill me-2"></i>Open regular admin panel
                        </a>
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="admin-feature-panel p-4 h-100">
                        <h4 class="mb-3"><i class="bi bi-info-circle-fill me-2"></i>Sign-in</h4>
                        <p class="mb-4 text-muted">
                            The browser will prompt for credentials when you open the protected dashboard. This page
                            is the team entry point and a simple reminder of what the area is for.
                        </p>
                        <div class="d-grid gap-2">
                            <div class="alert alert-success mb-0">
                                <i class="bi bi-check2-circle me-2"></i>Only for you and your team
                            </div>
                            <div class="alert alert-success mb-0">
                                <i class="bi bi-check2-circle me-2"></i>Separated from player and admin views
                            </div>
                            <div class="alert alert-success mb-0">
                                <i class="bi bi-check2-circle me-2"></i>Ready for future expansion
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
            <div class="col">
                <div class="admin-feature-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="mb-0"><i class="bi bi-people-fill me-2"></i>Users</h4>
                        <span class="badge bg-secondary">Coming soon</span>
                    </div>
                    <p class="text-muted mb-0">Remove users, adjust roles, and manage account lifecycles.</p>
                </div>
            </div>
            <div class="col">
                <div class="admin-feature-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="mb-0"><i class="bi bi-map-fill me-2"></i>Routes</h4>
                        <span class="badge bg-secondary">Coming soon</span>
                    </div>
                    <p class="text-muted mb-0">Delete routes, verify ownership, and apply system-level fixes.</p>
                </div>
            </div>
            <div class="col">
                <div class="admin-feature-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="mb-0"><i class="bi bi-shield-lock-fill me-2"></i>Permissions</h4>
                        <span class="badge bg-secondary">Coming soon</span>
                    </div>
                    <p class="text-muted mb-0">Change system admin and admin permissions in a controlled way.</p>
                </div>
            </div>
            <div class="col">
                <div class="admin-feature-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i>Logs</h4>
                        <span class="badge bg-secondary">Coming soon</span>
                    </div>
                    <p class="text-muted mb-0">Track changes, actions, and potential anomalies in a later phase.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>
</body>
</html>
