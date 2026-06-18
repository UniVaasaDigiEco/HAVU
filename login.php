<?php
require_once('classes/security.class.php');
require_once('classes/message.class.php');

Security::initSession();
$preserved_flash_messages = $_SESSION['flash_messages'] ?? [];

Security::logout();
Security::initSession();

if (!empty($preserved_flash_messages)) {
    $_SESSION['flash_messages'] = $preserved_flash_messages;
}

$return_to = trim((string)($_GET['return_to'] ?? ''));
$return_to_query = $return_to !== '' ? ('?return_to=' . urlencode($return_to)) : '';
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
            echo Message::displayFlashMessages();

            if (isset($_GET['error'])) {
                $error_code = intval($_GET['error']);
                echo Message::error($error_code);
            }
            if (isset($_GET['registered'])) {
                echo "<div class='col-12 col-lg-3 alert alert-success alert-dismissible fade show' role='alert'>
                    <i class='bi bi-check-circle-fill me-2'></i>" . htmlspecialchars(t('login.registered_success'), ENT_QUOTES, 'UTF-8') . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='" . htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') . "'></button>
                </div>";
            }
            if (isset($_GET['reset']) && $_GET['reset'] === '1') {
                echo "<div class='col-12 col-lg-3 alert alert-success alert-dismissible fade show' role='alert'>
                    <i class='bi bi-check-circle-fill me-2'></i>" . htmlspecialchars(t('login.password_reset_success'), ENT_QUOTES, 'UTF-8') . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='" . htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') . "'></button>
                </div>";
            }
            if (isset($_GET['account_deleted']) && $_GET['account_deleted'] === '1') {
                echo "<div class='col-12 col-lg-3 alert alert-success alert-dismissible fade show' role='alert'>
                    <i class='bi bi-check-circle-fill me-2'></i>" . htmlspecialchars(t('login.account_deleted_success'), ENT_QUOTES, 'UTF-8') . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='" . htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') . "'></button>
                </div>";
            }
            ?>
            <div class="container-fluid col-12 col-lg-3 bg-secondary-subtle p-4 rounded-3">
                <form action="actions/login.php" method="POST">
                    <?php if ($return_to !== ''): ?>
                        <input type="hidden" name="return_to" value="<?= htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="email" class="form-label"><?= htmlspecialchars(t('login.username_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input id="email" name="email" type="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><?= htmlspecialchars(t('login.password_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input id="password" name="password" type="password" class="form-control">
                    </div>
                    <div class="mb-3 text-end">
                        <a href="forgot-password.php" class="small"><?= htmlspecialchars(t('login.forgot_password_link'), ENT_QUOTES, 'UTF-8') ?></a>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary text-white w-100"><i class="bi bi-box-arrow-in-right me-1"></i> <?= htmlspecialchars(t('common.log_in'), ENT_QUOTES, 'UTF-8') ?></button>
                    </div>
                </form>
            </div>
            <p class="mt-3 text-muted">
                <?= htmlspecialchars(t('login.no_account'), ENT_QUOTES, 'UTF-8') ?>
                <a href="register.php<?= htmlspecialchars($return_to_query, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(t('login.create_free_account'), ENT_QUOTES, 'UTF-8') ?></a>
            </p>
            <p class="mb-2">
                <a href="<?= htmlspecialchars(ROOT_DIR . 'privacy/privacy_notice_' . current_locale() . '.pdf', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="text-muted small">
                    <?= htmlspecialchars(t('feedback.privacy'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </p>
            <p>
                <a href="index.php" class="btn btn-warning">
                    <i class="bi bi-arrow-left"></i> <?= htmlspecialchars(t('common.back_to_home'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </p>
        </div>
    </div>
</body>
</html>
