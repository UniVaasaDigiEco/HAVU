<?php
require_once __DIR__ . '/../classes/security.class.php';

$admin_nav_current = $admin_nav_current ?? '';
$admin_route_nav_active = in_array($admin_nav_current, ['dashboard', 'new-route', 'edit-route', 'delete-route'], true);
$show_system_admin_link = false;

try {
    if (!empty($_SESSION['user_public_id']) && Security::isCurrentUserSystemAdmin()) {
        $show_system_admin_link = true;
    }
} catch (Exception $e) {
    $show_system_admin_link = false;
}
?>
<nav class="navbar navbar-expand-lg admin-navbar" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand admin-navbar__brand" href="dashboard.php">
            <span class="admin-navbar__badge">
                <img src="../../images/havu_logo_map.svg" alt="HAVU" class="admin-navbar__badge-image">
            </span>
            <span>
                <?= htmlspecialchars(t('common.app_name'), ENT_QUOTES, 'UTF-8') ?>
                <small class="d-block"><?= htmlspecialchars(t('common.admin_panel'), ENT_QUOTES, 'UTF-8') ?></small>
            </span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbarNav" aria-controls="adminNavbarNav" aria-expanded="false" aria-label="<?= htmlspecialchars(t('common.admin_panel'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbarNav">
            <ul class="navbar-nav me-auto mb-3 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link<?= $admin_route_nav_active ? ' active' : '' ?>" href="dashboard.php"<?= $admin_route_nav_active ? ' aria-current="page"' : '' ?>>
                        <i class="bi bi-map-fill me-2"></i><?= htmlspecialchars(t('admin_dashboard.route_management'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $admin_nav_current === 'route-statistics' ? ' active' : '' ?>" href="route-statistics.php"<?= $admin_nav_current === 'route-statistics' ? ' aria-current="page"' : '' ?>>
                        <i class="bi bi-bar-chart-line-fill me-2"></i><?= htmlspecialchars(t('admin_dashboard.route_statistics'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $admin_nav_current === 'messages' ? ' active' : '' ?>" href="messages.php"<?= $admin_nav_current === 'messages' ? ' aria-current="page"' : '' ?>>
                        <i class="bi bi-inbox-fill me-2"></i><?= htmlspecialchars(t('common.messages'), ENT_QUOTES, 'UTF-8') ?>
                        <?php 
                        try {
                            require_once __DIR__ . '/../classes/messagecenter.class.php';
                            require_once __DIR__ . '/../classes/tools.class.php';
                            if (!empty($_SESSION['user_public_id'])) {
                                $current_user_id = Tools::getUserIdByPublicId($_SESSION['user_public_id']);
                                $unread_count = MessageCenter::getUnreadCount($current_user_id);
                                if ($unread_count > 0) {
                                    echo '<span class="badge bg-danger ms-2">' . (int)$unread_count . '</span>';
                                }
                            }
                        } catch (Exception $e) {
                            // Non-fatal
                        }
                        ?>
                    </a>
                </li>
                <?php if ($show_system_admin_link): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../../system-admin/">
                            <i class="bi bi-shield-lock-fill me-2"></i>System admin
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex flex-column flex-lg-row gap-2 admin-navbar__actions">
                <a href="../player/dashboard.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars(t('common.my_profile'), ENT_QUOTES, 'UTF-8') ?>
                </a>
                <?php
                $language_switcher_mode = 'navbar';
                require __DIR__ . '/_language_switcher.php';
                ?>
                <a href="../../actions/logout.php" class="btn btn-sm btn-warning">
                    <i class="bi bi-box-arrow-right me-1"></i><?= htmlspecialchars(t('common.log_out'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
        </div>
    </div>
</nav>
