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
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAVU - Gamification - Luo tunnus</title>
    <link rel="stylesheet" href="css/bs-custom.css">
    <link rel="stylesheet" href="node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container-fluid vh-100">
    <div class="row h-100">
        <div class="col-12 d-flex flex-column justify-content-center align-items-center">
            <img src="images/havu_logo.png" alt="HAVU Logo" class="mb-4" style="max-width: 300px;">
            <h1 class="mb-4">Luo tunnus</h1>

            <?php if (isset($_GET['error'])): ?>
                <div class="col-12 col-lg-4 alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i>
                    <?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Sulje"></button>
                </div>
            <?php endif; ?>

            <div class="container-fluid col-12 col-lg-4 bg-secondary-subtle p-4 rounded-3">
                <form action="actions/register.php" method="POST">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nimi</label>
                        <input id="full_name" name="full_name" type="text" class="form-control"
                               value="<?= htmlspecialchars($_GET['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required autocomplete="name">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Sähköposti</label>
                        <input id="email" name="email" type="email" class="form-control"
                               value="<?= htmlspecialchars($_GET['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Salasana</label>
                        <input id="password" name="password" type="password" class="form-control"
                               required autocomplete="new-password" minlength="8">
                        <small class="text-muted">Vähintään 8 merkkiä</small>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">Vahvista salasana</label>
                        <input id="password_confirm" name="password_confirm" type="password" class="form-control"
                               required autocomplete="new-password">
                    </div>
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="request_admin" name="request_admin">
                            <label class="form-check-label fw-semibold" for="request_admin">
                                Haluatko lisätä reittejä itse?
                            </label>
                        </div>
                        <small id="request_admin_hint" class="text-muted" style="display:none;">
                            <i class="bi bi-info-circle me-1"></i>
                            Tunnuksesi pelaajana luodaan ja aktivoidaan heti. Ylläpitäjälle lähetetään ilmoitus pyynnostäsi luoda reittejä. Saat reitinluontioikeudet, kun pyyntösi on hyväksytty.
                        </small>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-success text-white w-100">
                            <i class="bi bi-person-plus-fill me-1"></i> Luo tunnus
                        </button>
                    </div>
                </form>
            </div>

            <p class="mt-3 text-muted">
                Onko sinulla jo tunnus?
                <a href="login.php">Kirjaudu sisään</a>
            </p>
            <p>
                <a href="#" class="text-muted small" onclick="event.preventDefault(); window.history.back();">
                    <i class="bi bi-arrow-left"></i> Takaisin
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
