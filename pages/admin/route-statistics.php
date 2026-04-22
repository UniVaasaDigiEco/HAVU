<?php
require_once('../../config/constants.php');
require_once('../../classes/security.class.php');
require_once('../../classes/message.class.php');

Security::initSession();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('admin_route_statistics.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/x-icon" href="../../favicon.ico">
    <link rel="stylesheet" href="../../css/bs-custom.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
<body class="admin-dashboard has-site-footer">
<?php
$admin_nav_current = 'route-statistics';
require_once '../../includes/_admin_nav.php';
?>
<div class="container-fluid py-4">
    <div class="admin-page-content">
        <div class="p-4 bg-secondary-subtle rounded-3 shadow">
            <div class="mb-4">
                <h3><i class="bi bi-bar-chart-line-fill me-2"></i><?= htmlspecialchars(t('admin_route_statistics.heading'), ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="lead mb-0"><?= htmlspecialchars(t('admin_route_statistics.intro'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?= Message::displayFlashMessages() ?>
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle-fill me-2"></i><?= htmlspecialchars(t('admin_route_statistics.placeholder'), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
    </div>
</div>
<?php require_once '../../includes/_footer.php'; ?>
</body>
</html>
