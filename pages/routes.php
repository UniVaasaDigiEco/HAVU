<?php
require_once('../config/constants.php');
require_once('../classes/tools.class.php');
require_once('../classes/security.class.php');

Security::initSession();

$is_logged_in = !empty($_SESSION['user_public_id']);
$is_admin     = !empty($_SESSION['is_admin']);
$user_id      = null;

// For logged-in players, load their completion and progress data
$completed_route_ids  = []; // route_id => completed_at
$in_progress_counts   = []; // route_id => visited_node_count

if ($is_logged_in && !$is_admin) {
    try {
        $user_id = Tools::getUserIdByPublicId($_SESSION['user_public_id']);

        $db = Tools::getDb();

        $stmt = $db->prepare("SELECT route_id, completed_at FROM route_completions WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($rc_route_id, $rc_completed_at);
        while ($stmt->fetch()) {
            $completed_route_ids[$rc_route_id] = $rc_completed_at;
        }
        $stmt->close();

        $stmt = $db->prepare("SELECT route_id, COUNT(DISTINCT node_id) AS cnt FROM node_visits WHERE user_id = ? GROUP BY route_id");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($nv_route_id, $nv_cnt);
        while ($stmt->fetch()) {
            $in_progress_counts[$nv_route_id] = $nv_cnt;
        }
        $stmt->close();

        $db->close();
    } catch (Exception $e) {
        // Non-fatal — just show routes without progress data
    }
}

// Fetch all published routes with node count
$routes = [];
try {
    $db = Tools::getDb();
    $sql = "SELECT r.id, r.public_id, r.title, r.description,
                   COUNT(nrc.id) AS node_count
            FROM routes r
            LEFT JOIN node_route_cross nrc ON nrc.route_id = r.id
            WHERE r.is_published = 1
            GROUP BY r.id, r.public_id, r.title, r.description
            ORDER BY r.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $stmt->bind_result($r_id, $r_public_id, $r_title, $r_description, $r_node_count);
    while ($stmt->fetch()) {
        $routes[] = [
            'id'          => $r_id,
            'public_id'   => $r_public_id,
            'title'       => $r_title,
            'description' => $r_description,
            'node_count'  => $r_node_count,
        ];
    }
    $stmt->close();
    $db->close();
} catch (Exception $e) {
    $routes = [];
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('routes.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="stylesheet" href="../css/bs-custom.css">
    <link rel="stylesheet" href="../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
<body class="has-site-footer">
<nav class="navbar navbar-expand-lg bg-primary" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="../index.php">
            <img src="../images/havu_logo.png" alt="HAVU" height="30" class="me-2">
            <?= htmlspecialchars(t('common.app_name'), ENT_QUOTES, 'UTF-8') ?>
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <?php if ($is_admin): ?>
                <a href="admin/dashboard.php" class="btn btn-sm btn-warning">
                    <i class="bi bi-gear-fill me-1"></i><?= htmlspecialchars(t('common.admin_panel'), ENT_QUOTES, 'UTF-8') ?>
                </a>
                <?php
                $language_switcher_mode = 'navbar';
                require '../includes/_language_switcher.php';
                ?>
                <a href="../actions/logout.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right me-1"></i><?= htmlspecialchars(t('common.log_out'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php elseif ($is_logged_in): ?>
                <a href="player/dashboard.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars(t('common.my_profile'), ENT_QUOTES, 'UTF-8') ?>
                </a>
                <?php
                $language_switcher_mode = 'navbar';
                require '../includes/_language_switcher.php';
                ?>
                <a href="../actions/logout.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right me-1"></i><?= htmlspecialchars(t('common.log_out'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php else: ?>
                <a href="../login.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-in-right me-1"></i><?= htmlspecialchars(t('common.log_in'), ENT_QUOTES, 'UTF-8') ?>
                </a>
                <?php
                $language_switcher_mode = 'navbar';
                require '../includes/_language_switcher.php';
                ?>
                <a href="../register.php" class="btn btn-sm btn-light">
                    <i class="bi bi-person-plus-fill me-1"></i><?= htmlspecialchars(t('common.create_account'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold"><?= htmlspecialchars(t('routes.heading'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="lead text-muted"><?= htmlspecialchars(t('routes.intro'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="text-muted">
            <a target="_blank" href="files/Pikaopas_HAVUpelaaminen.pdf"><?= htmlspecialchars(t('routes.quick_guide'), ENT_QUOTES, 'UTF-8') ?></a>
        </p>
        <?php if (!$is_logged_in): ?>
            <p class="text-muted small">
                <i class="bi bi-info-circle me-1"></i>
                <?= t('routes.register_prompt') ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if (empty($routes)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-map" style="font-size: 3rem;"></i>
            <p class="mt-3"><?= htmlspecialchars(t('routes.empty'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
            <?php foreach ($routes as $route):
                $route_id    = $route['id'];
                $is_complete = isset($completed_route_ids[$route_id]);
                $visited     = $in_progress_counts[$route_id] ?? 0;
                $total       = $route['node_count'];
                $is_started  = $visited > 0 && !$is_complete;
                $pct         = $total > 0 ? round($visited / $total * 100) : 0;
            ?>
                <div class="col">
                    <div class="card h-100 shadow-sm <?= $is_complete ? 'border-success' : '' ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($route['title'], ENT_QUOTES, 'UTF-8') ?></h5>
                                <?php if ($is_complete): ?>
                                    <span class="badge bg-success ms-2 text-nowrap">
                                        <i class="bi bi-check-circle-fill me-1"></i><?= htmlspecialchars(t('common.status_completed'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php elseif ($is_started): ?>
                                    <span class="badge bg-warning text-dark ms-2 text-nowrap">
                                        <i class="bi bi-hourglass-split me-1"></i><?= htmlspecialchars(t('common.status_in_progress'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p class="card-text text-muted small">
                                <?= htmlspecialchars($route['description'], ENT_QUOTES, 'UTF-8') ?>
                            </p>

                            <div class="d-flex align-items-center gap-2 mb-3 text-muted small">
                                <i class="bi bi-geo-alt-fill text-primary"></i>
                                <span><?= htmlspecialchars(t('common.node_count', ['count' => $total]), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <?php if ($is_started && $is_logged_in): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span><?= htmlspecialchars(t('common.progress'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span><?= $visited ?>/<?= $total ?></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-warning" style="width: <?= $pct ?>%"></div>
                                    </div>
                                </div>
                            <?php elseif ($is_complete && $is_logged_in): ?>
                                <div class="mb-3">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: 100%"></div>
                                    </div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars(t('common.route_completed_on', ['date' => date('d.m.Y', strtotime($completed_route_ids[$route_id]))]), ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent border-0 pb-3">
                            <a href="game.php?route=<?= htmlspecialchars($route['public_id'], ENT_QUOTES, 'UTF-8') ?>"
                               class="btn btn-primary w-100">
                                <i class="bi bi-joystick me-1"></i>
                                <?= htmlspecialchars($is_complete ? t('common.play_again') : ($is_started ? t('common.continue_route') : t('common.start_route')), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once '../includes/_footer.php'; ?>
</body>
</html>
