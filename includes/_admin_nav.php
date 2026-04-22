<?php
$admin_nav_current = $admin_nav_current ?? '';
$admin_nav_items = [
    [
        'id' => 'dashboard',
        'href' => 'dashboard.php',
        'icon' => 'bi bi-grid-fill',
        'label' => t('common.admin_panel'),
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
                <?php foreach ($admin_nav_items as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $admin_nav_current === $item['id'] ? ' active' : '' ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"<?= $admin_nav_current === $item['id'] ? ' aria-current="page"' : '' ?>>
                            <i class="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?> me-2"></i><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
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
