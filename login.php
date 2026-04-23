<?php
require_once('classes/security.class.php');
Security::logout();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('login.title'), ENT_QUOTES, 'UTF-8') ?></title>
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
            <img src="images/havu_logo.png" alt="HAVU Logo" class="mb-4" style="max-width: 400px;">
            <h1 class="mb-4"><?= htmlspecialchars(t('login.heading'), ENT_QUOTES, 'UTF-8') ?></h1>
            <?php
            if (isset($_GET['error'])) {
                require_once('classes/message.class.php');
                $error_code = intval($_GET['error']);
                echo Message::error($error_code);
            }
            if (isset($_GET['registered'])) {
                echo "<div class='col-12 col-lg-3 alert alert-success alert-dismissible fade show' role='alert'>
                    <i class='bi bi-check-circle-fill me-2'></i>" . htmlspecialchars(t('login.registered_success'), ENT_QUOTES, 'UTF-8') . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='" . htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') . "'></button>
                </div>";
            }
            ?>
            <div class="container-fluid col-12 col-lg-3 bg-secondary-subtle p-4 rounded-3">
                <form action="actions/login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label"><?= htmlspecialchars(t('login.username_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input id="email" name="email" type="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><?= htmlspecialchars(t('login.password_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input id="password" name="password" type="password" class="form-control">
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary text-white w-100"><i class="bi bi-box-arrow-in-right me-1"></i> <?= htmlspecialchars(t('common.log_in'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                </form>
            </div>
            <p class="mt-3 text-muted">
                <?= htmlspecialchars(t('login.no_account'), ENT_QUOTES, 'UTF-8') ?>
                <a href="register.php"><?= htmlspecialchars(t('login.create_free_account'), ENT_QUOTES, 'UTF-8') ?></a>
            </p>
            <p>
                <a href="index.php" class="text-muted small">
                    <i class="bi bi-arrow-left"></i> <?= htmlspecialchars(t('common.back_to_home'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </p>
        </div>
    </div>
</body>
</html>
