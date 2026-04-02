<?php
require_once('../../config/constants.php');
require_once('../../classes/tools.class.php');
require_once('../../classes/security.class.php');
require_once('../../classes/message.class.php');

Security::initSession();

try {
    $user = Tools::getUserWithPublicId($_SESSION['user_public_id']);
} catch (Exception $e) {
    die("Error fetching user: " . $e->getMessage());
}

$routes = $user->getCreatedRoutes();

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$game_base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . ROOT_DIR . 'pages/game.php?route=';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HAVU - Gamification - Ylläpitäjän hallintapaneeli</title>
    <link rel="stylesheet" href="../../css/bs-custom.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="../../node_modules/jquery/dist/jquery.min.js"></script>
    <script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="admin-dashboard">
<div class="container-fluid">
    <div class="d-flex flex-row py-3">
        <div id="menu" class="col-2 pe-2" style="position: sticky; top: 1rem; align-self: flex-start;">
            <div id="menu-content" class="p-3 bg-primary-subtle rounded-3 shadow">
                <h3 class="text-center"><i class="bi bi-gear-fill me-2"></i>Ylläpitäjän hallintapaneeli</h3>
                <button class="btn btn-primary text-white w-100" id="show-route-management" data-target="#route-management">
                    <i class="bi bi-map-fill"></i> Hallitse reittejä
                </button>
                <a class="btn btn-primary text-white w-100" id="goto-game" href="../routes.php"><i class="bi bi-joystick"></i> Siirry peliin</a>
                <a class="btn btn-primary text-white w-100" id="logout" href="../../actions/logout.php"><i class="bi bi-box-arrow-left me-1"></i> Kirjaudu ulos</a>
            </div>
        </div>
        <div id="dashboard-content" class="col-10">
            <div id="route-management" class="p-4 bg-secondary-subtle rounded-3 shadow">
                <div id="header" class="mb-4">
                    <h3><i class="bi bi-map-fill me-2"></i>Reittien hallinta</h3>
                    <?php
                    echo Message::displayFlashMessages();
                    ?>
                    <!--<p class="lead">Here you can manage the routes for the game. Create new routes, edit existing ones, or remove routes that are no longer needed.</p>-->
                    <p class="lead">Tästä voit hallita reittejäsi. Luo uusia reittejä, muokkaa olemassa olevia, tai poista reittejä, joita ei enää tarvita.</p>
                    <p>
                        <a href="files/user_guide_FI.docx" download>Lataa ohjeet tästä</a>
                    </p>
                </div>
                <div id="route-management-controls" class="d-flex flex-wrap gap-2">
                    <a href="new-route.php" class="btn btn-primary text-white" id="btn-newRoute">
                        <i class="bi bi-plus-circle-fill"></i> Lisää uusi reitti
                    </a>
                    <a href="edit-route.php" class="btn btn-primary text-white" id="btn-editRoute">
                        <i class="bi bi-pencil-fill"></i> Muokkaa reittiä
                    </a>
                    <a href="delete-route.php" class="btn btn-danger text-white" id="btn-deleteRoute">
                        <i class="bi bi-trash-fill"></i> Poista reitti
                    </a>
                </div>
                <div id="route-testing" class="mt-4 route-management-section">
                    <h3><i class="bi bi-map-fill me-2"></i>Reittien testaaminen</h3>
                    <div class="mb-3">
                        <label for="route-select" class="form-label">Valitse testattava reitti, ja klikkaa "Pelaa" testataksesi reittiä:</label>
                        <select class="form-select" id="route-select">
                            <option selected disabled>Valitse reitti</option>
                            <?php
                            if($routes){
                                foreach ($routes as $route){
                                    echo "<option value='{$route->getPublicId()}'>{$route->getTitle()}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <button type="button" id="btn-play" class="btn btn-primary text-white"><i class="bi bi-joystick"></i> Pelaa</button>
                    <script>
                        $('#btn-play').on('click', function(){
                            const selectedRoutePublicId = $('#route-select').val();
                            if(!selectedRoutePublicId){
                                alert("Please select a route to test.");
                                return;
                            }
                            window.location.href = `testGame.php?route=${selectedRoutePublicId}`;
                        });
                    </script>
                </div>

                <div id="route-sharing" class="mt-4" style="border-top: 2px solid rgba(123,162,90,.2); margin-top: 3rem; padding-top: 2rem;">
                    <h3><i class="bi bi-share-fill me-2"></i>Reittien jakaminen</h3>
                    <p class="lead">Jaa reitti pelaajille linkin tai QR-koodin avulla. Julkiset reitit näkyvät kaikille pelaajille; yksityiset ovat pelattavissa vain jaetun linkin kautta.</p>
                    <?php if ($routes): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Reitti</th>
                                    <th>Tila</th>
                                    <th>Toiminnot</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($routes as $route): ?>
                                <tr>
                                    <td><?= htmlspecialchars($route->getTitle(), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($route->getIsPublished()): ?>
                                            <span class="badge bg-success"><i class="bi bi-eye me-1"></i>Julkinen</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-eye-slash me-1"></i>Yksityinen</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button class="btn btn-sm btn-outline-primary btn-share"
                                                    data-route-id="<?= htmlspecialchars($route->getPublicId(), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-route-title="<?= htmlspecialchars($route->getTitle(), ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-qr-code me-1"></i>Jaa
                                            </button>
                                            <form action="../../actions/toggle_publish.php" method="POST" class="m-0">
                                                <input type="hidden" name="route_public_id" value="<?= htmlspecialchars($route->getPublicId(), ENT_QUOTES, 'UTF-8') ?>">
                                                <?php if ($route->getIsPublished()): ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                                        <i class="bi bi-eye-slash me-1"></i>Tee yksityiseksi
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                                        <i class="bi bi-eye me-1"></i>Julkaise
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <p class="text-muted">Sinulla ei ole vielä reittejä.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareModalLabel">
                    <i class="bi bi-share me-2"></i>Jaa: <span id="shareModalTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Sulje"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted mb-3">
                    <label for="shareUrl">Jaa tämä linkki tai QR-koodi pelaajille — he pääsevät reitille suoraan, myös ilman kirjautumista.</label>
                </p>
                <div class="input-group mb-4">
                    <input type="text" class="form-control font-monospace small" id="shareUrl" readonly>
                    <button class="btn btn-outline-secondary" id="btnCopyUrl" type="button" title="Kopioi linkki">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <img id="shareQr" src="" alt="QR-koodi" class="border rounded p-2" style="width:200px;height:200px;">
                <p class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>QR-koodi vie suoraan reitille</p>
            </div>
        </div>
    </div>
</div>

<script>
    const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
    const gameBaseUrl = <?= json_encode($game_base_url) ?>;

    document.querySelectorAll('.btn-share').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const routeId = this.dataset.routeId;
            const routeTitle = this.dataset.routeTitle;
            const gameUrl = gameBaseUrl + routeId;

            document.getElementById('shareModalTitle').textContent = routeTitle;
            document.getElementById('shareUrl').value = gameUrl;
            document.getElementById('shareQr').src =
                'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(gameUrl);

            shareModal.show();
        });
    });

    document.getElementById('btnCopyUrl').addEventListener('click', function() {
        navigator.clipboard.writeText(document.getElementById('shareUrl').value).then(() => {
            this.innerHTML = '<i class="bi bi-check text-success"></i>';
            setTimeout(() => { this.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 2000);
        });
    });
</script>
</body>
</html>
