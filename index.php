<?php
require_once('config/constants.php');
require_once('classes/security.class.php');

Security::initSession();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('index.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/x-icon" href="./favicon.ico">
    <link rel="stylesheet" href="css/bs-custom.css">
    <link rel="stylesheet" href="node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php require_once 'includes/_language_switcher.php'; ?>
<div class="container-fluid min-vh-100 d-flex flex-column">
    <div class="flex-grow-1 d-flex flex-column justify-content-center align-items-center text-center py-4">
        <img src="images/havu_logo.png" alt="HAVU Logo" class="mb-4 img-fluid w-100" style="max-width: 400px;">
        <h1 class="mb-4"><?= htmlspecialchars(t('index.heading'), ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if (MAINTENANCE_MODE): ?>
            <div class="card shadow-sm border-warning-subtle" style="max-width: 42rem;">
                <div class="card-body p-4 text-center">
                    <div class="text-warning-emphasis mb-3" style="font-size: 2.5rem;">
                        <i class="bi bi-tools"></i>
                    </div>
                    <h2 class="h3 mb-3"><?= htmlspecialchars(t('index.maintenance_heading'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="lead mb-3"><?= htmlspecialchars(t('index.maintenance_message'), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-muted mb-0"><?= htmlspecialchars(t('index.maintenance_retry'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="d-flex flex-row gap-3 flex-wrap justify-content-center">
                <a href="login.php" class="btn btn-success btn-lg text-white fw-bold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> <?= htmlspecialchars(t('common.log_in'), ENT_QUOTES, 'UTF-8') ?>
                </a>
                <a href="register.php" class="btn btn-outline-primary btn-lg fw-bold">
                    <i class="bi bi-person-plus-fill me-1"></i> <?= htmlspecialchars(t('common.create_account'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
            <div class="d-flex flex-row gap-3 flex-wrap justify-content-center mt-4">
                <a href="https://havupeli.jansoftworks.fi/pages/game.php?route=1bcf5a18-cb65-4fda-b841-134dce683b47" class="btn btn-success btn-lg text-white fw-bold" target="_blank" rel="noopener">
                    <i class="bi bi-joystick"></i> <?= htmlspecialchars(t('index.test_game'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
    <div id="logoContainer" class="mt-auto">
        <div class="logo-row logo-row--primary">
            <img id="co-funded-eu-logo" alt="Logo, Co-Funded by the European Union" src="images/logos/cofunded-eu.png">
            <img id="epliitto" alt="Logo, Eteläpohjanmaan liitto" src="images/logos/epliitto.svg">
        </div>
        <div class="logo-row logo-row--secondary">
            <img id="vyy" alt="Logo, Vaasan yliopisto" src="images/logos/vyy.svg">
            <img id="seamk" alt="Logo, Seinäjoen Ammattikorkeakoulu" src="images/logos/seamk.png">
            <img id="metsakeskus" alt="Logo, Metsäkeskus" src="images/logos/metsakeskus.png">
        </div>
    </div>
</div>
</body>
</html>
