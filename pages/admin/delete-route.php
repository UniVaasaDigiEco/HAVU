<?php
require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ .'/../../classes/message.class.php');
require_once(__DIR__ .'/../../classes/tools.class.php');
require_once(__DIR__ .'/../../classes/security.class.php');
use Ramsey\Uuid\Uuid;
Security::initSession();

if (empty($_SESSION['user_public_id'])) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message_key' => 'login.session_expired',
    ];
    header('Location: ../../login.php');
    exit;
}

$user_public_id_string = $_SESSION['user_public_id'];

try {
    $user = Tools::getUserWithPublicId($user_public_id_string);
} catch (Exception $e) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message_key' => 'login.session_expired',
    ];
    header('Location: ../../login.php');
    exit;
}
$routes = $user->getCreatedRoutes();
$delete_route_confirmation = json_encode(
    t('route_editor.confirm_delete_route'),
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('admin_delete_route.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/x-icon" href="../../favicon.ico">
    <link rel="stylesheet" href="../../css/bs-custom.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
<body class="admin-dashboard has-site-footer">
<?php
$admin_nav_current = 'delete-route';
require_once '../../includes/_admin_nav.php';
?>
<div class="container-fluid py-4" style="margin-bottom: 101px;">
    <div class="row h-100 align-items-center justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="admin-page-hero p-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h3><i class="bi bi-trash-fill me-2"></i><?= htmlspecialchars(t('admin_delete_route.heading'), ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="lead mb-0"><?= htmlspecialchars(t('admin_delete_route.subheading'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <a href="dashboard.php" class="btn btn-warning">
                        <i class="bi bi-arrow-left"></i> <?= htmlspecialchars(t('common.back_to_dashboard'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>
            </div>
            <div class="p-5 bg-white rounded-3 shadow text-center">
                <i class="bi bi-trash-fill text-danger" style="font-size: 4rem;"></i>
                <?= Message::displayFlashMessages() ?>
                <form action="../../actions/delete-route.php" method="post" class="mt-4" onsubmit='return confirm(<?= $delete_route_confirmation ?>)'>
                    <div class="mb-3">
                        <label for="route_select" class="form-label"><?= htmlspecialchars(t('admin_delete_route.select_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="route_public_id" id="route_select" class="form-select" required>
                            <option value="" selected disabled><?= htmlspecialchars(t('admin_delete_route.select_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php
                            foreach($routes as $route) {
                                $route_public_id = htmlspecialchars($route->getPublicId(), ENT_QUOTES, 'UTF-8');
                                $route_title = htmlspecialchars($route->getTitle(), ENT_QUOTES, 'UTF-8');
                                echo "<option value='$route_public_id'>$route_title</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash-fill"></i> <?= htmlspecialchars(t('admin_delete_route.submit'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once '../../includes/_footer.php'; ?>
</body>
</html>
