<?php
require_once('../../config/constants.php');
require_once('../../classes/tools.class.php');
require_once('../../classes/security.class.php');
require_once('../../classes/message.class.php');

Security::initSession();

try {
    $user = Tools::getUserWithPublicId($_SESSION['user_public_id']);
} catch (Exception $e) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message_key' => 'login.session_expired',
    ];
    header('Location: ../../login.php');
    exit;
}

$routes = $user->getCreatedRoutes();

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$game_base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . ROOT_DIR . 'pages/game.php?route=';
$delete_route_confirmation = json_encode(
    t('route_editor.confirm_delete_route'),
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);
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
        <div id="route-management" class="admin-feature-panel p-4 bg-secondary-subtle rounded-3 shadow">
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
                </div>
                <div class="route-management-table">
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
                                                <span class="badge bg-success" data-bs-toggle="tooltip" data-bs-title="<?= htmlspecialchars(t('common.public'), ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-eye"></i><span class="d-none d-md-inline ms-1"><?= htmlspecialchars(t('common.public'), ENT_QUOTES, 'UTF-8') ?></span></span>
                                        <?php else: ?>
                                                <span class="badge bg-secondary" data-bs-toggle="tooltip" data-bs-title="<?= htmlspecialchars(t('common.private'), ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-eye-slash"></i><span class="d-none d-md-inline ms-1"><?= htmlspecialchars(t('common.private'), ENT_QUOTES, 'UTF-8') ?></span></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="route-actions-cell">
                                        <!-- Desktop: full labelled buttons -->
                                        <div class="d-none d-md-flex gap-2 flex-wrap">
                                            <form action="../../actions/toggle_publish.php" method="POST" class="m-0">
                                                <input type="hidden" name="route_public_id" value="<?= htmlspecialchars($route->getPublicId(), ENT_QUOTES, 'UTF-8') ?>">
                                                <?php if ($route->getIsPublished()): ?>
                                                        <button type="submit" class="btn btn-sm btn-warning route-toggle-btn">
                                                        <i class="bi bi-eye-slash me-1"></i><?= htmlspecialchars(t('admin_dashboard.make_private'), ENT_QUOTES, 'UTF-8') ?>
                                                    </button>
                                                <?php else: ?>
                                                        <button type="submit" class="btn btn-sm btn-success route-toggle-btn">
                                                        <i class="bi bi-eye me-1"></i><?= htmlspecialchars(t('admin_dashboard.publish'), ENT_QUOTES, 'UTF-8') ?>
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                            <a href="edit-route.php?route_public_id=<?= urlencode($route->getPublicId()) ?>" class="btn btn-sm btn-secondary">
                                                <i class="bi bi-pencil-square me-1"></i><?= htmlspecialchars(t('admin_dashboard.edit_route'), ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                            <button class="btn btn-sm btn-secondary btn-share text-white"
                                                data-route-id="<?= htmlspecialchars($route->getPublicId(), ENT_QUOTES, 'UTF-8') ?>"
                                                data-route-title="<?= htmlspecialchars($route->getTitle(), ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-qr-code me-1"></i><?= htmlspecialchars(t('admin_dashboard.share'), ENT_QUOTES, 'UTF-8') ?>
                                            </button>
                                                <button class="btn btn-sm btn-info btn-copy-route"
                                                    data-route-id="<?= htmlspecialchars($route->getPublicId(), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-route-title="<?= htmlspecialchars($route->getTitle(), ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-files me-1"></i><?= htmlspecialchars(t('admin_dashboard.copy_route'), ENT_QUOTES, 'UTF-8') ?>
                                            </button>
                                                <a href="testGame.php?route=<?= urlencode($route->getPublicId()) ?>" class="btn btn-sm btn-dark">
                                                <i class="bi bi-joystick me-1"></i><?= htmlspecialchars(t('admin_dashboard.test_route'), ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                            <form action="../../actions/delete-route.php" method="POST" class="m-0 js-delete-route-form">
                                                <input type="hidden" name="route_public_id" value="<?= htmlspecialchars($route->getPublicId(), ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash me-1"></i><?= htmlspecialchars(t('admin_dashboard.delete_route'), ENT_QUOTES, 'UTF-8') ?>
                                                </button>
                                            </form>
                                        </div>
                                        <!-- Mobile: collapsed dropdown -->
                                        <div class="d-md-none">
                                            <div class="dropdown">
                                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                    <span class="visually-hidden"><?= htmlspecialchars(t('admin_dashboard.table_actions'), ENT_QUOTES, 'UTF-8') ?></span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <form action="../../actions/toggle_publish.php" method="POST" class="m-0">
                                                            <input type="hidden" name="route_public_id" value="<?= htmlspecialchars($route->getPublicId(), ENT_QUOTES, 'UTF-8') ?>">
                                                            <?php if ($route->getIsPublished()): ?>
                                                                <button type="submit" class="dropdown-item text-warning">
                                                                    <i class="bi bi-eye-slash me-2"></i><?= htmlspecialchars(t('admin_dashboard.make_private'), ENT_QUOTES, 'UTF-8') ?>
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="submit" class="dropdown-item text-success">
                                                                    <i class="bi bi-eye me-2"></i><?= htmlspecialchars(t('admin_dashboard.publish'), ENT_QUOTES, 'UTF-8') ?>
                                                                </button>
                                                            <?php endif; ?>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="edit-route.php?route_public_id=<?= urlencode($route->getPublicId()) ?>">
                                                            <i class="bi bi-pencil-square me-2"></i><?= htmlspecialchars(t('admin_dashboard.edit_route'), ENT_QUOTES, 'UTF-8') ?>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item btn-share"
                                                                data-route-id="<?= htmlspecialchars($route->getPublicId(), ENT_QUOTES, 'UTF-8') ?>"
                                                                data-route-title="<?= htmlspecialchars($route->getTitle(), ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="bi bi-qr-code me-2"></i><?= htmlspecialchars(t('admin_dashboard.share'), ENT_QUOTES, 'UTF-8') ?>
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item btn-copy-route"
                                                                data-route-id="<?= htmlspecialchars($route->getPublicId(), ENT_QUOTES, 'UTF-8') ?>"
                                                                data-route-title="<?= htmlspecialchars($route->getTitle(), ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="bi bi-files me-2"></i><?= htmlspecialchars(t('admin_dashboard.copy_route'), ENT_QUOTES, 'UTF-8') ?>
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="testGame.php?route=<?= urlencode($route->getPublicId()) ?>">
                                                            <i class="bi bi-joystick me-2"></i><?= htmlspecialchars(t('admin_dashboard.test_route'), ENT_QUOTES, 'UTF-8') ?>
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="../../actions/delete-route.php" method="POST" class="m-0 js-delete-route-form">
                                                            <input type="hidden" name="route_public_id" value="<?= htmlspecialchars($route->getPublicId(), ENT_QUOTES, 'UTF-8') ?>">
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="bi bi-trash me-2"></i><?= htmlspecialchars(t('admin_dashboard.delete_route'), ENT_QUOTES, 'UTF-8') ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
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

<!-- Copy Route Modal -->
<div class="modal fade" id="copyRouteModal" tabindex="-1" aria-labelledby="copyRouteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="../../actions/copy-route.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="copyRouteModalLabel">
                        <i class="bi bi-files me-2"></i><?= htmlspecialchars(t('admin_dashboard.copy_modal_title'), ENT_QUOTES, 'UTF-8') ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3"><?= htmlspecialchars(t('admin_dashboard.copy_modal_intro'), ENT_QUOTES, 'UTF-8') ?></p>
                    <input type="hidden" id="copyRoutePublicId" name="route_public_id" required>
                    <div class="mb-3">
                        <label for="copyRouteTitle" class="form-label"><?= htmlspecialchars(t('admin_dashboard.copy_name_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" class="form-control" id="copyRouteTitle" name="route_title" required>
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars(t('admin_dashboard.copy_name_help'), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <?= htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="bi bi-files me-1"></i><?= htmlspecialchars(t('admin_dashboard.copy_submit'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
            </form>
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
                <img id="shareQr" src="" alt="QR-koodi" class="border rounded p-2 admin-share-qr">
                <p class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars(t('admin_dashboard.qr_info'), ENT_QUOTES, 'UTF-8') ?></p>
                <div class="mt-3">
                    <button class="btn btn-sm btn-primary" id="btnDownloadQr" type="button" title="<?= htmlspecialchars(t('admin_dashboard.download_qr'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-download me-1"></i><?= htmlspecialchars(t('admin_dashboard.download_qr'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const shareModalElement = document.getElementById('shareModal');
    const shareModal = new bootstrap.Modal(shareModalElement);
    const copyRouteModalElement = document.getElementById('copyRouteModal');
    const copyRouteModal = new bootstrap.Modal(copyRouteModalElement);

    // Blur any focused element inside a modal before it hides to prevent the
    // aria-hidden-on-focused-descendant warning (Bootstrap 5 known issue).
    [shareModalElement, copyRouteModalElement].forEach(function(el) {
        el.addEventListener('hide.bs.modal', function() {
            if (el.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });
    });
    const gameBaseUrl = <?= json_encode($game_base_url) ?>;
    const copyNamePrefix = 'Copy of ';
    const deleteRouteConfirmation = <?= $delete_route_confirmation ?>;

    // Initialise tooltips on status badges
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el, { trigger: 'hover focus click' });
    });

    // Mobile dropdowns: use fixed positioning so they overflow the table-responsive container
    document.querySelectorAll('.d-md-none [data-bs-toggle="dropdown"]').forEach(function(el) {
        new bootstrap.Dropdown(el, {
            popperConfig: { strategy: 'fixed' }
        });
    });

    document.querySelectorAll('.js-delete-route-form').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!confirm(deleteRouteConfirmation)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('.btn-copy-route').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const routeId = this.dataset.routeId;
            const routeTitle = this.dataset.routeTitle;

            document.getElementById('copyRoutePublicId').value = routeId;
            document.getElementById('copyRouteTitle').value = `${copyNamePrefix}${routeTitle}`;

            copyRouteModal.show();
        });
    });

    copyRouteModalElement.addEventListener('shown.bs.modal', function() {
        const titleInput = document.getElementById('copyRouteTitle');
        titleInput.focus();
        titleInput.select();
    });

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

    document.getElementById('btnDownloadQr').addEventListener('click', function() {
        const qrImg = document.getElementById('shareQr');
        const shareUrl = document.getElementById('shareUrl').value;
        const fileName = 'route-qr-code.png';

        fetch(qrImg.src)
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            })
            .catch(err => {
                console.error('QR code download failed:', err);
                alert('Failed to download QR code');
            });
    });
</script>
<?php require_once '../../includes/_footer.php'; ?>
</body>
</html>
