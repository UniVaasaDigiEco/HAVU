<?php
require_once('config/constants.php');
require_once('classes/security.class.php');

Security::initSession();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars(t('index.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="css/bs-custom.css">
    <link rel="stylesheet" href="node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php require_once 'includes/_language_switcher.php'; ?>
<div class="container-fluid vh-100">
    <div class="row h-100">
        <div class="col-12 d-flex flex-column justify-content-center align-items-center">
            <img src="images/havu_logo.png" alt="HAVU Logo" class="mb-4" style="max-width: 400px;">
            <h1 class="mb-4"><?= htmlspecialchars(t('index.heading'), ENT_QUOTES, 'UTF-8') ?></h1>
            <div class="d-flex flex-row gap-3 flex-wrap justify-content-center">
                <a href="pages/routes.php" class="btn btn-success btn-lg text-white fw-bold">
                    <i class="bi bi-joystick me-1"></i> <?= htmlspecialchars(t('common.play_now'), ENT_QUOTES, 'UTF-8') ?>
                </a>
                <a href="register.php" class="btn btn-outline-primary btn-lg fw-bold">
                    <i class="bi bi-person-plus-fill me-1"></i> <?= htmlspecialchars(t('common.create_account'), ENT_QUOTES, 'UTF-8') ?>
                </a>
                <a href="login.php" class="btn btn-success btn-lg text-white fw-bold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> <?= htmlspecialchars(t('common.log_in'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
