<?php
$admin_nav_current = $admin_nav_current ?? '';
$admin_route_items = [
    [
        'id' => 'dashboard',
        'href' => 'dashboard.php',
        'icon' => 'bi bi-grid-fill',
        'label' => t('admin_dashboard.dashboard_home'),
    ],
    [
        'id' => 'new-route',
        'href' => 'new-route.php',
        'icon' => 'bi bi-plus-circle-fill',
        'label' => t('admin_dashboard.new_route'),
    ],
    [
        'id' => 'edit-route',
        'href' => 'edit-route.php',
        'icon' => 'bi bi-pencil-fill',
        'label' => t('admin_dashboard.edit_route'),
    ],
    [
        'id' => 'delete-route',
        'href' => 'delete-route.php',
        'icon' => 'bi bi-trash-fill',
        'label' => t('admin_dashboard.delete_route'),
    ],
];
$admin_route_menu_active = in_array($admin_nav_current, ['dashboard', 'new-route', 'edit-route', 'delete-route'], true);
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
                <li class="nav-item dropdown admin-navbar__dropdown">
                    <a class="nav-link dropdown-toggle<?= $admin_route_menu_active ? ' active' : '' ?>"
                       href="#"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false">
                        <i class="bi bi-map-fill me-2"></i><?= htmlspecialchars(t('admin_dashboard.route_management'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <ul class="dropdown-menu">
                        <?php foreach ($admin_route_items as $item): ?>
                            <li>
                                <a class="dropdown-item<?= $admin_nav_current === $item['id'] ? ' active' : '' ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"<?= $admin_nav_current === $item['id'] ? ' aria-current="page"' : '' ?>>
                                    <i class="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?> me-2"></i><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
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
