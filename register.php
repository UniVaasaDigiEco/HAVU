<?php
require_once('config/constants.php');
require_once('classes/security.class.php');
Security::initSession();

// Redirect already-logged-in users
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
    <title><?= htmlspecialchars(t('register.title'), ENT_QUOTES, 'UTF-8') ?></title>
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
            <h1 class="mb-4"><?= htmlspecialchars(t('register.heading'), ENT_QUOTES, 'UTF-8') ?></h1>

            <?php if (isset($_GET['error']) || isset($_GET['error_key'])): ?>
                <div class="col-12 col-lg-4 alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i>
                    <?= htmlspecialchars(isset($_GET['error_key']) ? t($_GET['error_key']) : $_GET['error'], ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
                </div>
            <?php endif; ?>

            <div class="container-fluid col-12 col-lg-4 bg-secondary-subtle p-4 rounded-3">
                <form action="actions/register.php" method="POST">
                    <div class="mb-3">
                        <label for="full_name" class="form-label"><?= htmlspecialchars(t('register.name_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input id="full_name" name="full_name" type="text" class="form-control"
                               value="<?= htmlspecialchars($_GET['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required autocomplete="name">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label"><?= htmlspecialchars(t('register.email_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input id="email" name="email" type="email" class="form-control"
                               value="<?= htmlspecialchars($_GET['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><?= htmlspecialchars(t('register.password_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input id="password" name="password" type="password" class="form-control"
                               required autocomplete="new-password" minlength="8">
                        <small class="text-muted"><?= htmlspecialchars(t('register.password_hint'), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label"><?= htmlspecialchars(t('register.password_confirm_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input id="password_confirm" name="password_confirm" type="password" class="form-control"
                               required autocomplete="new-password">
                    </div>
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="request_admin" name="request_admin">
                            <label class="form-check-label fw-semibold" for="request_admin">
                                <?= htmlspecialchars(t('register.request_admin_label'), ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        </div>
                        <small id="request_admin_hint" class="text-muted" style="display:none;">
                            <i class="bi bi-info-circle me-1"></i>
                            <?= htmlspecialchars(t('register.request_admin_hint'), ENT_QUOTES, 'UTF-8') ?>
                        </small>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-success text-white w-100">
                            <i class="bi bi-person-plus-fill me-1"></i> <?= htmlspecialchars(t('common.create_account'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </div>
                </form>
            </div>

            <p class="mt-3 text-muted">
                <?= htmlspecialchars(t('register.has_account'), ENT_QUOTES, 'UTF-8') ?>
                <a href="login.php"><?= htmlspecialchars(t('common.log_in'), ENT_QUOTES, 'UTF-8') ?></a>
            </p>
            <p>
                <a href="#" class="text-muted small" onclick="event.preventDefault(); window.history.back();">
                    <i class="bi bi-arrow-left"></i> <?= htmlspecialchars(t('common.back'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </p>
        </div>
    </div>
</div>
<script>
    document.getElementById('request_admin').addEventListener('change', function () {
        document.getElementById('request_admin_hint').style.display = this.checked ? 'block' : 'none';
    });
</script>
</body>
</html>
