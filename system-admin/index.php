<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/security.class.php');
require_once(__DIR__ . '/../classes/message.class.php');
require_once(__DIR__ . '/../classes/system_admin_messagecenter.class.php');

Security::initSession();

if (empty($_SESSION['user_public_id'])) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message_key' => 'login.session_expired',
    ];
    header('Location: ' . ROOT_DIR . 'login.php');
    exit;
}

if (!Security::isCurrentUserSystemAdmin()) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'System admin access is required.',
    ];
    header('Location: ' . ROOT_DIR . 'pages/admin/dashboard.php');
    exit;
}

$unread_feedback_count = 0;
try {
    $feedback_summary = SystemAdminMessageCenter::getPage(1, 1, 'all');
    $unread_feedback_count = (int)($feedback_summary['counts']['unread'] ?? 0);
} catch (Exception $e) {
    $unread_feedback_count = 0;
}
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
        </div>
    </div>
</nav>

<div class="container-fluid py-4 mx-2">
    <div class="admin-page-content">
        <div class="admin-page-hero p-4 p-lg-5 mb-4">
            <div class="row align-items-start g-4">
                <div class="col-12 col-xl-8">
                    <span class="badge bg-dark text-white mb-3 px-3 py-2">Internal use only</span>
                    <h3 class="mb-3"><i class="bi bi-shield-lock-fill me-2"></i>System Administration</h3>
                    <p class="lead mb-0">
                        System-level control panel for your team. Manage users, routes, permissions, and audit logs —
                        separate from the regular admin panel.
                    </p>
                </div>
            </div>
        </div>

        <?= Message::displayFlashMessages(); ?>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
            <div class="col">
                <a href="./user-management.php" class="text-decoration-none">
                    <div class="admin-feature-panel p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="mb-0"><i class="bi bi-people-fill me-2"></i>Users</h4>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <p class="text-muted mb-0">List users, update account details, send password reset links, and deactivate or remove accounts.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="./messages.php" class="text-decoration-none">
                    <div class="admin-feature-panel p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="mb-0"><i class="bi bi-inbox-fill me-2"></i>Feedback inbox</h4>
                            <?php if ($unread_feedback_count > 0): ?>
                                <span class="badge bg-warning text-dark"><?= htmlspecialchars((string)$unread_feedback_count, ENT_QUOTES, 'UTF-8') ?> unread</span>
                            <?php else: ?>
                                <span class="badge bg-success">Active</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted mb-0">View and manage feedback form submissions as unread, read, and resolved items.</p>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="./bulk-message.php" class="text-decoration-none">
                    <div class="admin-feature-panel p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="mb-0"><i class="bi bi-envelope-fill me-2"></i>Bulk messaging</h4>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <p class="text-muted mb-0">Send a mass e-mail to all or selected users. Includes confirmation safeguards for large sends.</p>
                    </div>
                </a>
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
