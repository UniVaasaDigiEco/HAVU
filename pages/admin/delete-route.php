<?php
require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ .'/../../classes/message.class.php');
require_once(__DIR__ .'/../../classes/tools.class.php');
require_once(__DIR__ .'/../../classes/security.class.php');
use Ramsey\Uuid\Uuid;
Security::initSession();

$user_public_id_string = $_SESSION['user_public_id'];

try {
    $user = Tools::getUserWithPublicId($user_public_id_string);
} catch (Exception $e) {
    die("Error fetching user: " . $e->getMessage());
}
$routes = $user->getCreatedRoutes();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars(t('admin_delete_route.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/x-icon" href="../../favicon.ico">
    <link rel="stylesheet" href="../../css/bs-custom.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
<body class="admin-dashboard">
<?php require_once '../../includes/_language_switcher.php'; ?>
<div class="container-fluid vh-100">
    <div class="row py-3 h-100 align-items-center justify-content-center">
        <div class="col-md-6 text-center">
            <div class="p-5 bg-white rounded-3 shadow">
                <i class="bi bi-trash-fill text-danger" style="font-size: 4rem;"></i>
                <h2 class="mt-4"><?= htmlspecialchars(t('admin_delete_route.heading'), ENT_QUOTES, 'UTF-8') ?></h2>
                <?= Message::displayFlashMessages() ?>
                <form action="../../actions/delete-route.php" method="post" class="mt-4">
                    <div class="mb-3">
                        <label for="route_select" class="form-label"><?= htmlspecialchars(t('admin_delete_route.select_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="route_public_id" id="route_select" class="form-select">
                            <option value=""><?= htmlspecialchars(t('admin_delete_route.select_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php
                            foreach($routes as $route) {
                                $route_pubhlic_id = $route->getPublicId();
                                $route_title = $route->getTitle();
                                echo "<option value='$route_pubhlic_id'>$route_title</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-danger" onclick="return confirm(<?= json_encode(t('route_editor.confirm_delete_route'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)">
                        <i class="bi bi-trash-fill"></i> <?= htmlspecialchars(t('admin_delete_route.submit'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </form>
                <a href="dashboard.php" class="btn btn-primary mt-3">
                    <i class="bi bi-arrow-left"></i> <?= htmlspecialchars(t('common.back_to_dashboard'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
        </div>
    </div>
</div>
<?php require_once '../../includes/_feedback_widget.php'; ?>
</body>
</html>
