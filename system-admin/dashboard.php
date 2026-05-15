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
<nav class="navbar navbar-expand-lg admin-navbar" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand admin-navbar__brand" href="../system-admin/">
            <span class="admin-navbar__badge">
                <i class="bi bi-shield-lock-fill"></i>
            </span>
            <span>
                HAVU System Administration
                <small class="d-block">Internal control panel</small>
            </span>
        </a>
        <div class="d-flex flex-column flex-lg-row gap-2 admin-navbar__actions ms-lg-auto">
            <a href="../pages/admin/dashboard.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-gear-fill me-1"></i>Regular admin panel
            </a>
            <a href="../system-admin/" class="btn btn-sm btn-warning">
                <i class="bi bi-arrow-left me-1"></i>Back to entry page
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid py-4 mx-2">
    <div class="admin-page-content">
        <div class="admin-page-hero p-4 p-lg-5 mb-4">
            <div class="row align-items-start g-4">
                <div class="col-12 col-xl-8">
                    <span class="badge bg-dark text-white mb-3 px-3 py-2">System level</span>
                    <h3 class="mb-3"><i class="bi bi-diagram-3-fill me-2"></i>Administration Center</h3>
                    <p class="lead mb-4">
                        This area is reserved for system oversight. It will eventually hold tools for user management,
                        route management, permission changes, and other internal operations.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary text-white" disabled>
                            <i class="bi bi-people-fill me-2"></i>User management
                        </button>
                        <button type="button" class="btn btn-outline-secondary" disabled>
                            <i class="bi bi-map-fill me-2"></i>Route control
                        </button>
                        <button type="button" class="btn btn-outline-secondary" disabled>
                            <i class="bi bi-shield-lock-fill me-2"></i>Permission editing
                        </button>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="admin-feature-panel p-4 h-100">
                        <h4 class="mb-3"><i class="bi bi-lightning-charge-fill me-2"></i>Phase one</h4>
                        <p class="text-muted mb-0">
                            The page currently acts as a clean landing shell. Actual tools will be added in stages once
                            the exact permissions and workflows are defined.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
            <div class="col">
                <div class="admin-feature-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="mb-0"><i class="bi bi-people-fill me-2"></i>Users</h4>
                        <span class="badge bg-success">Planned</span>
                    </div>
                    <p class="text-muted mb-0">Review, remove, and reassign user roles.</p>
                </div>
            </div>
            <div class="col">
                <div class="admin-feature-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="mb-0"><i class="bi bi-map-fill me-2"></i>Routes</h4>
                        <span class="badge bg-success">Planned</span>
                    </div>
                    <p class="text-muted mb-0">Handle route deletions, ownership checks, and cleanup tasks.</p>
                </div>
            </div>
            <div class="col">
                <div class="admin-feature-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="mb-0"><i class="bi bi-key-fill me-2"></i>Permissions</h4>
                        <span class="badge bg-success">Planned</span>
                    </div>
                    <p class="text-muted mb-0">Adjust who can access admin and system-admin functionality.</p>
                </div>
            </div>
            <div class="col">
                <div class="admin-feature-panel p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i>Logs</h4>
                        <span class="badge bg-success">Planned</span>
                    </div>
                    <p class="text-muted mb-0">Audit future system actions and operational changes.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>
</body>
</html>