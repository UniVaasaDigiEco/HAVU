<?php
require_once('config/constants.php');
require_once('classes/security.class.php');

Security::initSession();

if (!empty($_SESSION['user_public_id'])) {
    header('Location: ' . ROOT_DIR . 'pages/player/dashboard.php');
    exit;
}

$token = trim($_GET['token'] ?? '');
$updated = isset($_GET['updated']) && $_GET['updated'] === '1';
$error_key = $_GET['error_key'] ?? null;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('password_reset.reset_title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/x-icon" href="./favicon.ico">
    <link rel="stylesheet" href="css/bs-custom.css">
    <link rel="stylesheet" href="node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php require_once 'includes/_language_switcher.php'; ?>
<div class="container-fluid vh-100">
    <div class="row h-100">
        <div class="col-12 d-flex flex-column justify-content-center align-items-center">
            <img src="images/havu_logo.png" alt="HAVU Logo" class="mb-4" style="max-width: 300px;">
            <h1 class="mb-3"><?= htmlspecialchars(t('password_reset.reset_heading'), ENT_QUOTES, 'UTF-8') ?></h1>

            <?php if (is_string($error_key) && $error_key !== ''): ?>
                <div class="col-12 col-lg-4 alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i>
                    <?= htmlspecialchars(t($error_key), ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
                </div>
            <?php endif; ?>

            <?php if ($updated): ?>
                <div class="col-12 col-lg-4 alert alert-success" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= htmlspecialchars(t('password_reset.reset_success'), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <p class="mt-2"><a href="login.php"><?= htmlspecialchars(t('password_reset.back_to_login'), ENT_QUOTES, 'UTF-8') ?></a></p>
            <?php else: ?>
                <p class="text-muted mb-4 text-center col-12 col-lg-4">
                    <?= htmlspecialchars(t('password_reset.reset_intro'), ENT_QUOTES, 'UTF-8') ?>
                </p>

                <div class="container-fluid col-12 col-lg-4 bg-secondary-subtle p-4 rounded-3">
                    <form action="actions/reset-password.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::getCsrfToken('password_reset_submit'), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-3">
                            <label for="new_password" class="form-label"><?= htmlspecialchars(t('password_reset.new_password_label'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input id="new_password" name="new_password" type="password" class="form-control" minlength="8" required autocomplete="new-password">
                            <small class="text-muted"><?= htmlspecialchars(t('password_reset.password_hint'), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                        <div class="mb-3">
                            <label for="new_password_confirm" class="form-label"><?= htmlspecialchars(t('password_reset.new_password_confirm_label'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input id="new_password_confirm" name="new_password_confirm" type="password" class="form-control" minlength="8" required autocomplete="new-password">
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary text-white w-100">
                                <i class="bi bi-shield-lock me-1"></i><?= htmlspecialchars(t('password_reset.reset_submit'), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </div>
                    </form>
                </div>

                <p class="mt-3 text-muted">
                    <a href="login.php"><?= htmlspecialchars(t('password_reset.back_to_login'), ENT_QUOTES, 'UTF-8') ?></a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
