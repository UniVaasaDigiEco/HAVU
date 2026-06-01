<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/security.class.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/message.class.php');

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

function formatSystemAdminDate(?string $date_string): string
{
    if ($date_string === null || trim($date_string) === '') {
        return '-';
    }

    try {
        $value = new DateTime($date_string);
        return current_locale() === 'en' ? $value->format('Y-m-d H:i') : $value->format('d.m.Y H:i');
    } catch (Exception $e) {
        return '-';
    }
}

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? 'all');
if (!in_array($status, ['all', 'active', 'inactive'], true)) {
    $status = 'all';
}
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;

$result = Tools::getSystemAdminUserPage($search, $status, $page, $per_page);
$users = $result['users'];
$total_users = $result['total'];
$total_pages = $result['total_pages'];
$csrf_token = Security::getCsrfToken('system_admin_user_management');
$current_user_public_id = (string)($_SESSION['user_public_id'] ?? '');
$return_to = $_SERVER['REQUEST_URI'] ?? (ROOT_DIR . 'system-admin/user-management.php');
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
                <i class="bi bi-arrow-left me-1"></i>Back to dashboard
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
                    <h3 class="mb-3"><i class="bi bi-diagram-3-fill me-2"></i>User management</h3>
                    <p class="lead mb-4">
                        Manage user accounts from one place: view registrations, update account details,
                        trigger password reset links, and deactivate or remove accounts when needed.
                    </p>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="admin-feature-panel p-4 h-100">
                        <h4 class="mb-3"><i class="bi bi-lightning-charge-fill me-2"></i>Current scope</h4>
                        <p class="text-muted mb-0">
                            Phase one includes listing users, updating name and account status,
                            sending password reset links, and safeguarded account removal actions.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?= Message::displayFlashMessages(); ?>

        <div class="admin-feature-panel p-4 mb-4">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-12 col-lg-6">
                    <label for="search" class="form-label mb-1">Search users</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        class="form-control"
                        value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Name or email">
                </div>
                <div class="col-12 col-lg-3">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12 col-lg-3 d-grid">
                    <button type="submit" class="btn btn-primary text-white">
                        <i class="bi bi-funnel-fill me-1"></i>Apply filters
                    </button>
                </div>
            </form>
            <div class="mt-3 text-muted small">
                <?= htmlspecialchars((string)$total_users, ENT_QUOTES, 'UTF-8') ?> users found.
            </div>
        </div>

        <div class="admin-feature-panel p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="min-width: 320px;">User</th>
                        <th style="min-width: 180px;">Registered</th>
                        <th style="min-width: 180px;">Last login</th>
                        <th style="min-width: 250px;">Account</th>
                        <th style="min-width: 340px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No users match the current filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $managed_user): ?>
                            <?php
                            $is_self = $managed_user['public_id'] === $current_user_public_id;
                            $is_system_admin = Security::isSystemAdminPublicId($managed_user['public_id']);
                            $is_active = (int)$managed_user['is_active'] === 1;
                            ?>
                            <tr>
                                <td>
                                    <form method="post" action="../actions/system-admin-update-user.php" class="row g-2">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="user_public_id" value="<?= htmlspecialchars($managed_user['public_id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="return_to" value="<?= htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8') ?>">

                                        <div class="col-12">
                                            <label class="form-label mb-1">Full name</label>
                                            <input
                                                type="text"
                                                name="full_name"
                                                class="form-control form-control-sm"
                                                minlength="2"
                                                maxlength="120"
                                                value="<?= htmlspecialchars($managed_user['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                required>
                                        </div>
                                        <div class="col-8 col-md-7">
                                            <label class="form-label mb-1">Account status</label>
                                            <select name="is_active" class="form-select form-select-sm">
                                                <option value="1" <?= $is_active ? 'selected' : '' ?>>Active</option>
                                                <option value="0" <?= !$is_active ? 'selected' : '' ?>>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-4 col-md-5 d-grid align-self-end">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                        </div>
                                    </form>
                                    <div class="small mt-2 text-muted">
                                        <?= htmlspecialchars($managed_user['email'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($is_system_admin): ?>
                                            <span class="badge bg-dark ms-2">system-admin</span>
                                        <?php endif; ?>
                                        <?php if ($is_self): ?>
                                            <span class="badge bg-secondary ms-1">you</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars(formatSystemAdminDate($managed_user['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(formatSystemAdminDate($managed_user['last_login']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if ($is_active): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-grid gap-2">
                                        <form method="post" action="../actions/system-admin-password-reset.php">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="user_public_id" value="<?= htmlspecialchars($managed_user['public_id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="return_to" value="<?= htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-dark w-100" <?= !$is_active ? 'disabled' : '' ?>>
                                                <i class="bi bi-envelope-fill me-1"></i>Send password reset link
                                            </button>
                                        </form>

                                        <form method="post" action="../actions/system-admin-delete-user.php" class="d-flex gap-2">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="user_public_id" value="<?= htmlspecialchars($managed_user['public_id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="return_to" value="<?= htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="mode" value="<?= $is_active ? 'deactivate' : 'activate' ?>">
                                            <button type="submit" class="btn btn-sm <?= $is_active ? 'btn-outline-warning' : 'btn-outline-success' ?> flex-grow-1" <?= $is_self ? 'disabled' : '' ?>>
                                                <?= $is_active ? 'Deactivate account' : 'Reactivate account' ?>
                                            </button>
                                        </form>

                                        <form method="post" action="../actions/system-admin-delete-user.php" class="d-flex gap-2" onsubmit="return confirm('This will permanently delete user data. Continue?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="user_public_id" value="<?= htmlspecialchars($managed_user['public_id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="return_to" value="<?= htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="mode" value="hard_delete">
                                            <input type="text" name="confirm_text" class="form-control form-control-sm" placeholder="Type DELETE" required>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" <?= $is_self ? 'disabled' : '' ?>>Hard delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav class="mt-3" aria-label="User pagination">
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                        <?php
                        $page_url = ROOT_DIR . 'system-admin/user-management.php?status=' . urlencode($status)
                            . '&search=' . urlencode($search)
                            . '&page=' . $p;
                        ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars($page_url, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$p, ENT_QUOTES, 'UTF-8') ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>
</body>
</html>