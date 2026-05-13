<?php
require_once('config/constants.php');
require_once('classes/security.class.php');

Security::initSession();

if (!empty($_SESSION['user_public_id'])) {
    header('Location: ' . ROOT_DIR . 'pages/routes.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('password_reset.request_title'), ENT_QUOTES, 'UTF-8') ?></title>
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
            <h1 class="mb-3"><?= htmlspecialchars(t('password_reset.request_heading'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-muted mb-4 text-center col-12 col-lg-4">
                <?= htmlspecialchars(t('password_reset.request_intro'), ENT_QUOTES, 'UTF-8') ?>
            </p>

            <?php if (isset($_GET['sent']) && $_GET['sent'] === '1'): ?>
                <div class="col-12 col-lg-4 alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-envelope-check me-2"></i>
                    <?= htmlspecialchars(t('password_reset.request_sent_generic'), ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
                </div>
            <?php endif; ?>

            <div class="container-fluid col-12 col-lg-4 bg-secondary-subtle p-4 rounded-3">
                <form action="actions/request-password-reset.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::getCsrfToken('forgot_password_request'), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label"><?= htmlspecialchars(t('password_reset.email_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input id="email" name="email" type="email" class="form-control" required autocomplete="email">
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary text-white w-100">
                            <i class="bi bi-envelope-paper me-1"></i><?= htmlspecialchars(t('password_reset.request_submit'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </div>
                </form>
            </div>

            <p class="mt-3 text-muted">
                <a href="login.php"><?= htmlspecialchars(t('password_reset.back_to_login'), ENT_QUOTES, 'UTF-8') ?></a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
