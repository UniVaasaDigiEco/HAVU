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
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('admin_dashboard.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/x-icon" href="../../favicon.ico">
    <link rel="stylesheet" href="../../css/bs-custom.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="../../node_modules/jquery/dist/jquery.min.js"></script>
    <script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
<body class="admin-dashboard has-site-footer">
<?php
$admin_nav_current = 'dashboard';
require_once '../../includes/_admin_nav.php';
?>
<div class="container-fluid py-4">
    <div id="dashboard-content" class="admin-page-content">
        <div id="route-management" class="p-4 bg-secondary-subtle rounded-3 shadow">
                <div id="header" class="mb-4">
                    <h3><i class="bi bi-map-fill me-2"></i><?= htmlspecialchars(t('admin_dashboard.route_management'), ENT_QUOTES, 'UTF-8') ?></h3>
                    <?php
                    echo Message::displayFlashMessages();
                    ?>
                    <p class="lead"><?= htmlspecialchars(t('admin_dashboard.route_management_intro'), ENT_QUOTES, 'UTF-8') ?></p>
                    <p>
                        <?= htmlspecialchars(t('admin_dashboard.manuals_intro'), ENT_QUOTES, 'UTF-8') ?><br>
                        <a target="_blank" href="files/HAVU_reitinluojan_opas.pdf"><?= htmlspecialchars(t('admin_dashboard.route_creator_guide'), ENT_QUOTES, 'UTF-8') ?></a>
                        <br>
                        <a target="_blank" href="files/HAVU_pelaajanopas.pdf"><?= htmlspecialchars(t('admin_dashboard.player_guide'), ENT_QUOTES, 'UTF-8') ?></a>
                        <br>
                        <a target="_blank" href="files/Pikaopas_HAVUpelaaminen.pdf"><?= htmlspecialchars(t('admin_dashboard.quick_guide'), ENT_QUOTES, 'UTF-8') ?></a>
                    </p>
                    <p>
                        <a href="files/user_guide_FI.docx" download><?= htmlspecialchars(t('admin_dashboard.download_old_guide'), ENT_QUOTES, 'UTF-8') ?></a>
                    </p>
                </div>
                <div id="route-management-controls" class="d-flex flex-wrap gap-2">
                    <a href="new-route.php" class="btn btn-primary text-white" id="btn-newRoute">
                        <i class="bi bi-plus-circle-fill"></i> <?= htmlspecialchars(t('admin_dashboard.new_route'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <a href="edit-route.php" class="btn btn-primary text-white" id="btn-editRoute">
                        <i class="bi bi-pencil-fill"></i> <?= htmlspecialchars(t('admin_dashboard.edit_route'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <a href="delete-route.php" class="btn btn-danger text-white" id="btn-deleteRoute">
                        <i class="bi bi-trash-fill"></i> <?= htmlspecialchars(t('admin_dashboard.delete_route'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>
                <div id="route-testing" class="mt-4 route-management-section">
                    <h3><i class="bi bi-map-fill me-2"></i><?= htmlspecialchars(t('admin_dashboard.route_testing'), ENT_QUOTES, 'UTF-8') ?></h3>
                    <div class="mb-3">
                        <label for="route-select" class="form-label"><?= htmlspecialchars(t('admin_dashboard.route_testing_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select class="form-select" id="route-select">
                            <option selected disabled><?= htmlspecialchars(t('admin_dashboard.choose_route'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php
                            if($routes){
                                foreach ($routes as $route){
                                    echo "<option value='{$route->getPublicId()}'>{$route->getTitle()}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <button type="button" id="btn-play" class="btn btn-primary text-white"><i class="bi bi-joystick"></i> <?= htmlspecialchars(t('admin_dashboard.play'), ENT_QUOTES, 'UTF-8') ?></button>
                    <script>
                        $('#btn-play').on('click', function(){
                            const selectedRoutePublicId = $('#route-select').val();
                            if(!selectedRoutePublicId){
                                alert(<?= json_encode(t('admin_dashboard.choose_route_alert'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);
                                return;
                            }
                            window.location.href = `testGame.php?route=${selectedRoutePublicId}`;
                        });
                    </script>
                </div>

                <div id="route-sharing" class="mt-4" style="border-top: 2px solid rgba(123,162,90,.2); margin-top: 3rem; padding-top: 2rem;">
                    <h3><i class="bi bi-share-fill me-2"></i><?= htmlspecialchars(t('admin_dashboard.route_sharing'), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="lead"><?= htmlspecialchars(t('admin_dashboard.route_sharing_intro'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($routes): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th><?= htmlspecialchars(t('admin_dashboard.table_route'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('admin_dashboard.table_status'), ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars(t('admin_dashboard.table_actions'), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($routes as $route): ?>
                                <tr>
                                    <td><?= htmlspecialchars($route->getTitle(), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($route->getIsPublished()): ?>
                                            <span class="badge bg-success"><i class="bi bi-eye me-1"></i><?= htmlspecialchars(t('common.public'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-eye-slash me-1"></i><?= htmlspecialchars(t('common.private'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button class="btn btn-sm btn-outline-primary btn-share"
                                                    data-route-id="<?= htmlspecialchars($route->getPublicId(), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-route-title="<?= htmlspecialchars($route->getTitle(), ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-qr-code me-1"></i><?= htmlspecialchars(t('admin_dashboard.share'), ENT_QUOTES, 'UTF-8') ?>
                                            </button>
                                            <form action="../../actions/toggle_publish.php" method="POST" class="m-0">
                                                <input type="hidden" name="route_public_id" value="<?= htmlspecialchars($route->getPublicId(), ENT_QUOTES, 'UTF-8') ?>">
                                                <?php if ($route->getIsPublished()): ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                                        <i class="bi bi-eye-slash me-1"></i><?= htmlspecialchars(t('admin_dashboard.make_private'), ENT_QUOTES, 'UTF-8') ?>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                                        <i class="bi bi-eye me-1"></i><?= htmlspecialchars(t('admin_dashboard.publish'), ENT_QUOTES, 'UTF-8') ?>
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
                        <p class="text-muted"><?= htmlspecialchars(t('admin_dashboard.no_routes'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
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
                    <i class="bi bi-share me-2"></i><?= htmlspecialchars(t('admin_dashboard.share_modal_title'), ENT_QUOTES, 'UTF-8') ?><span id="shareModalTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted mb-3">
                    <label for="shareUrl"><?= htmlspecialchars(t('admin_dashboard.share_modal_intro'), ENT_QUOTES, 'UTF-8') ?></label>
                </p>
                <div class="input-group mb-4">
                    <input type="text" class="form-control font-monospace small" id="shareUrl" readonly>
                    <button class="btn btn-outline-secondary" id="btnCopyUrl" type="button" title="<?= htmlspecialchars(t('admin_dashboard.copy_link'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <img id="shareQr" src="" alt="QR-koodi" class="border rounded p-2" style="width:200px;height:200px;">
                <p class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars(t('admin_dashboard.qr_info'), ENT_QUOTES, 'UTF-8') ?></p>
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
<?php require_once '../../includes/_footer.php'; ?>
</body>
</html>
