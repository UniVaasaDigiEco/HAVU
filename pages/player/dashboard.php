<?php
require_once('../../config/constants.php');
require_once('../../classes/tools.class.php');
require_once('../../classes/security.class.php');
require_once('../../classes/message.class.php');

Security::initSession();

if (empty($_SESSION['user_public_id'])) {
    header('Location: ../../login.php');
    exit;
}

try {
    $user_id = Tools::getUserIdByPublicId($_SESSION['user_public_id']);
    $user    = Tools::getUserWithPublicId($_SESSION['user_public_id']);
} catch (Exception $e) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message_key' => 'login.session_expired',
    ];
    header('Location: ../../login.php');
    exit;
}

// ---- Fetch progress data ----

$completed_routes  = []; // [{id, public_id, title, node_count, completed_at}]
$in_progress       = []; // [{id, public_id, title, node_count, visited}]
$available_routes  = []; // [{id, public_id, title, node_count}]

try {
    $db = Tools::getDb();

    // Completed routes
    $sql = "SELECT r.id, r.public_id, r.title,
                   COUNT(nrc.id) AS node_count,
                   rc.completed_at
            FROM route_completions rc
            JOIN routes r ON r.id = rc.route_id
            LEFT JOIN node_route_cross nrc ON nrc.route_id = r.id
            WHERE rc.user_id = ?
            GROUP BY r.id, r.public_id, r.title, rc.completed_at
            ORDER BY rc.completed_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($r_id, $r_pub, $r_title, $r_nodes, $r_completed_at);
    while ($stmt->fetch()) {
        $completed_routes[] = [
            'id'           => $r_id,
            'public_id'    => $r_pub,
            'title'        => $r_title,
            'node_count'   => $r_nodes,
            'completed_at' => $r_completed_at,
        ];
    }
    $stmt->close();

    $completed_ids = array_column($completed_routes, 'id');

    // In-progress routes (has visits but not completed)
    $sql = "SELECT r.id, r.public_id, r.title,
                   COUNT(nrc.id) AS node_count,
                   COUNT(DISTINCT nv.node_id) AS visited
            FROM node_visits nv
            JOIN routes r ON r.id = nv.route_id
            LEFT JOIN node_route_cross nrc ON nrc.route_id = r.id
            WHERE nv.user_id = ?
            GROUP BY r.id, r.public_id, r.title
            ORDER BY MAX(nv.visited_at) DESC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($r_id, $r_pub, $r_title, $r_nodes, $r_visited);
    while ($stmt->fetch()) {
        if (!in_array($r_id, $completed_ids)) {
            $in_progress[] = [
                'id'         => $r_id,
                'public_id'  => $r_pub,
                'title'      => $r_title,
                'node_count' => $r_nodes,
                'visited'    => $r_visited,
            ];
        }
    }
    $stmt->close();

    $started_ids = array_merge($completed_ids, array_column($in_progress, 'id'));

    // Available routes not yet started
    $sql = "SELECT r.id, r.public_id, r.title, r.description,
                   COUNT(nrc.id) AS node_count
            FROM routes r
            LEFT JOIN node_route_cross nrc ON nrc.route_id = r.id
            WHERE r.is_published = 1
            GROUP BY r.id, r.public_id, r.title, r.description
            ORDER BY r.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $stmt->bind_result($r_id, $r_pub, $r_title, $r_desc, $r_nodes);
    while ($stmt->fetch()) {
        if (!in_array($r_id, $started_ids)) {
            $available_routes[] = [
                'id'          => $r_id,
                'public_id'   => $r_pub,
                'title'       => $r_title,
                'description' => $r_desc,
                'node_count'  => $r_nodes,
            ];
        }
    }
    $stmt->close();
    $db->close();

} catch (Exception $e) {
    // Non-fatal — show empty sections
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('player_dashboard.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/x-icon" href="../../favicon.ico">
    <link rel="stylesheet" href="../../css/bs-custom.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
<body class="has-site-footer">
<nav class="navbar navbar-expand-lg bg-primary" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="../../index.php">
            <img src="../../images/havu_logo.png" alt="HAVU" height="30" class="me-2">
            <?= htmlspecialchars(t('common.app_name'), ENT_QUOTES, 'UTF-8') ?>
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a href="../routes.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-map me-1"></i><?= htmlspecialchars(t('common.all_routes'), ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php if (!empty($_SESSION['is_admin'])): ?>
                <a href="../admin/dashboard.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-gear-fill me-1"></i><?= htmlspecialchars(t('common.admin_panel'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endif; ?>
            <?php
            $language_switcher_mode = 'navbar';
            require '../../includes/_language_switcher.php';
            ?>
            <a href="../../actions/logout.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-box-arrow-right me-1"></i><?= htmlspecialchars(t('common.log_out'), ENT_QUOTES, 'UTF-8') ?>
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <?php echo Message::displayFlashMessages(); ?>

    <!-- Profile header -->
    <div class="d-flex align-items-center gap-3 mb-5">
        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
             style="width:56px;height:56px;font-size:1.5rem;">
            <i class="bi bi-person-fill"></i>
        </div>
        <div>
            <h2 class="mb-0"><?= htmlspecialchars($user->getFullName(), ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="mt-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#accountSettingsModal">
                    <i class="bi bi-person-gear me-1"></i><?= htmlspecialchars(t('player_dashboard.account_settings_button'), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
            <span class="text-muted small"><?= htmlspecialchars($user->getEmail(), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="ms-auto text-end">
            <span class="badge bg-success fs-6 px-3 py-2">
                <i class="bi bi-check-circle-fill me-1"></i>
                <?= htmlspecialchars(t('player_dashboard.completed_summary', ['count' => count($completed_routes)]), ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
    </div>
    <div class="ms-auto mb-5">
        <h3><?= htmlspecialchars(t('player_dashboard.instructions'), ENT_QUOTES, 'UTF-8') ?></h3>
        <a href="../files/Pikaopas_HAVUpelaaminen.pdf"><?= htmlspecialchars(t('player_dashboard.quick_guide'), ENT_QUOTES, 'UTF-8') ?></a>
        <br>
        <a href="../files/HAVU_pelaajanopas.pdf"><?= htmlspecialchars(t('player_dashboard.full_guide'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>

    <!-- In progress -->
    <?php if (!empty($in_progress)): ?>
        <h4 class="mb-3"><i class="bi bi-hourglass-split text-warning me-2"></i><?= htmlspecialchars(t('common.in_progress_routes'), ENT_QUOTES, 'UTF-8') ?></h4>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-5">
            <?php foreach ($in_progress as $r):
                $pct = $r['node_count'] > 0 ? round($r['visited'] / $r['node_count'] * 100) : 0;
            ?>
                <div class="col">
                    <div class="card h-100 border-warning shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></h6>
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span><?= htmlspecialchars(t('player_dashboard.visited_nodes'), ENT_QUOTES, 'UTF-8') ?></span>
                                <span><?= $r['visited'] ?>/<?= $r['node_count'] ?></span>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pb-3">
                            <div class="d-grid gap-2">
                                <a href="../game.php?route=<?= htmlspecialchars($r['public_id'], ENT_QUOTES, 'UTF-8') ?>"
                                   class="btn btn-warning w-100">
                                    <i class="bi bi-play-fill me-1"></i><?= htmlspecialchars(t('common.continue_route'), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        onclick="window.openMessageModal(<?= htmlspecialchars(json_encode($r['id']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($r['title']), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i class="bi bi-chat-left-text me-1"></i>
                                    <?= htmlspecialchars(t('common.message_creator'), ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Completed routes -->
    <?php if (!empty($completed_routes)): ?>
        <h4 class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i><?= htmlspecialchars(t('common.completed_routes'), ENT_QUOTES, 'UTF-8') ?></h4>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-5">
            <?php foreach ($completed_routes as $r): ?>
                <div class="col">
                    <div class="card h-100 border-success shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></h6>
                            <div class="progress mb-2" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: 100%"></div>
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-calendar-check me-1"></i>
                                <?= htmlspecialchars(t('common.route_completed_on', ['date' => date('d.m.Y', strtotime($r['completed_at']))]), ENT_QUOTES, 'UTF-8') ?>
                            </small>
                        </div>
                        <div class="card-footer bg-transparent border-0 pb-3">
                            <div class="d-grid gap-2">
                                <a href="../game.php?route=<?= htmlspecialchars($r['public_id'], ENT_QUOTES, 'UTF-8') ?>"
                                   class="btn btn-outline-success w-100 btn-sm">
                                    <i class="bi bi-arrow-repeat me-1"></i><?= htmlspecialchars(t('common.play_again'), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        onclick="window.openMessageModal(<?= htmlspecialchars(json_encode($r['id']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($r['title']), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i class="bi bi-chat-left-text me-1"></i>
                                    <?= htmlspecialchars(t('common.message_creator'), ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Available routes -->
    <h4 class="mb-3"><i class="bi bi-map text-primary me-2"></i><?= htmlspecialchars(t('common.available_routes'), ENT_QUOTES, 'UTF-8') ?></h4>
    <?php if (empty($available_routes)): ?>
        <div class="text-center py-4 text-muted">
            <i class="bi bi-trophy-fill text-warning" style="font-size: 3rem;"></i>
            <p class="mt-3 fw-bold"><?= htmlspecialchars(t('player_dashboard.all_completed'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
            <?php foreach ($available_routes as $r): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></h6>
                            <p class="card-text text-muted small">
                                <?= htmlspecialchars($r['description'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <small class="text-muted">
                                <i class="bi bi-geo-alt-fill text-primary me-1"></i><?= htmlspecialchars(t('common.node_count', ['count' => $r['node_count']]), ENT_QUOTES, 'UTF-8') ?>
                            </small>
                        </div>
                        <div class="card-footer bg-transparent border-0 pb-3">
                            <div class="d-grid gap-2">
                                <a href="../game.php?route=<?= htmlspecialchars($r['public_id'], ENT_QUOTES, 'UTF-8') ?>"
                                   class="btn btn-primary w-100">
                                    <i class="bi bi-play-fill me-1"></i><?= htmlspecialchars(t('common.start_route'), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        onclick="window.openMessageModal(<?= htmlspecialchars(json_encode($r['id']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($r['title']), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i class="bi bi-chat-left-text me-1"></i>
                                    <?= htmlspecialchars(t('common.message_creator'), ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Include message widget for route creator messaging.
require_once '../../includes/_message-widget.php';
?>

<div class="modal fade" id="accountSettingsModal" tabindex="-1" aria-labelledby="accountSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountSettingsModalLabel">
                    <i class="bi bi-person-gear me-2"></i><?= htmlspecialchars(t('player_dashboard.account_settings_title'), ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3"><?= htmlspecialchars(t('player_dashboard.change_password_heading'), ENT_QUOTES, 'UTF-8') ?></h6>
                <form action="../../actions/update-account-password.php" method="POST" class="mb-4 pb-3 border-bottom">
                    <div class="mb-3">
                        <label for="current_password" class="form-label"><?= htmlspecialchars(t('player_dashboard.current_password_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label"><?= htmlspecialchars(t('player_dashboard.new_password_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                        <div class="form-text"><?= htmlspecialchars(t('player_dashboard.password_hint'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="new_password_confirm" class="form-label"><?= htmlspecialchars(t('player_dashboard.new_password_confirm_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-shield-lock me-1"></i><?= htmlspecialchars(t('player_dashboard.change_password_submit'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </form>

                <h6 class="mb-2 text-danger"><?= htmlspecialchars(t('player_dashboard.delete_account_heading'), ENT_QUOTES, 'UTF-8') ?></h6>
                <div class="alert alert-danger">
                    <strong><?= htmlspecialchars(t('player_dashboard.delete_account_warning_title'), ENT_QUOTES, 'UTF-8') ?></strong><br>
                    <?= htmlspecialchars(t('player_dashboard.delete_account_warning_body'), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <form action="../../actions/delete-account.php" method="POST" id="delete-account-form">
                    <div class="mb-3">
                        <label for="delete_confirm_input" class="form-label"><?= htmlspecialchars(t('player_dashboard.delete_account_confirm_label'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" class="form-control" id="delete_confirm_input" name="delete_confirm_input" autocomplete="off" required>
                        <div class="form-text"><?= htmlspecialchars(t('player_dashboard.delete_account_confirm_help'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <button type="submit" class="btn btn-danger" id="delete-account-submit" disabled>
                        <i class="bi bi-trash me-1"></i><?= htmlspecialchars(t('player_dashboard.delete_account_submit'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/_footer.php'; ?>
<script>
    (function () {
        const input = document.getElementById('delete_confirm_input');
        const button = document.getElementById('delete-account-submit');
        if (!input || !button) {
            return;
        }

        const updateDeleteButtonState = function () {
            button.disabled = input.value.trim() !== 'DELETE';
        };

        input.addEventListener('input', updateDeleteButtonState);
        updateDeleteButtonState();
    })();
</script>
</body>
</html>
