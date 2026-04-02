<?php
require_once('classes/security.class.php');
Security::logout();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HAVU - Gamification - Login</title>
    <link rel="stylesheet" href="css/bs-custom.css">
    <link rel="stylesheet" href="node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container-fluid vh-100">
    <div class="row h-100">
        <div class="col-12 d-flex flex-column justify-content-center align-items-center">
            <img src="images/havu_logo.png" alt="HAVU Logo" class="mb-4" style="max-width: 400px;">
            <h1 class="mb-4">HAVU-polkupeli</h1>
            <?php
            if (isset($_GET['error'])) {
                require_once('classes/message.class.php');
                $error_code = intval($_GET['error']);
                echo Message::error($error_code);
            }
            if (isset($_GET['registered'])) {
                echo "<div class='col-12 col-lg-3 alert alert-success alert-dismissible fade show' role='alert'>
                    <i class='bi bi-check-circle-fill me-2'></i>Tunnus luotu onnistuneesti! Voit nyt kirjautua sisään.
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Sulje'></button>
                </div>";
            }
            ?>
            <div class="container-fluid col-12 col-lg-3 bg-secondary-subtle p-4 rounded-3">
                <form action="actions/login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Tunnus</label>
                        <input id="email" name="email" type="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Salasana</label>
                        <input id="password" name="password" type="password" class="form-control">
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary text-white w-100"><i class="bi bi-box-arrow-in-right me-1"></i> Kirjaudu sisään</button>
                    </div>
                </form>
            </div>
            <p class="mt-3 text-muted">
                Ei tunnusta vielä?
                <a href="register.php">Luo tunnus ilmaiseksi</a>
            </p>
            <p>
                <a href="index.php" class="text-muted small">
                    <i class="bi bi-arrow-left"></i> Takaisin etusivulle
                </a>
            </p>
        </div>
    </div>
</body>
</html>