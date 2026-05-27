<?php
require_once('../config/constants.php');
require_once('../classes/tools.class.php');
require_once('../classes/security.class.php');

Security::initSession();

$creator_public_id = trim((string)($_GET['creator'] ?? ''));
$creator_name = '';
$routes = [];
$not_found = false;

if ($creator_public_id === '') {
    $not_found = true;
    http_response_code(404);
} else {
    try {
        $creator = Tools::getUserWithPublicId($creator_public_id);
        $creator_name = $creator->getFullName();

        $db = Tools::getDb();
        $sql = "
            SELECT
                r.public_id,
                r.title,
                r.description,
                r.created_at,
                COUNT(nrc.id) AS node_count
            FROM routes r
            LEFT JOIN node_route_cross nrc ON nrc.route_id = r.id
            WHERE r.user_id = ?
            GROUP BY r.id, r.public_id, r.title, r.description, r.created_at
            ORDER BY r.created_at DESC, r.id DESC
        ";

        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $creator_public_id);
        $stmt->execute();
        $stmt->bind_result($public_id, $title, $description, $created_at, $node_count);

        while ($stmt->fetch()) {
            $routes[] = [
                'public_id' => (string)$public_id,
                'title' => (string)$title,
                'description' => (string)($description ?? ''),
                'created_at' => (string)$created_at,
                'node_count' => (int)$node_count,
            ];
        }

        $stmt->close();
        $db->close();
    } catch (Exception $e) {
        $not_found = true;
        http_response_code(404);
    }
}

$page_title = $not_found
    ? t('creator_routes.not_found_title')
    : t('creator_routes.title', ['creator' => $creator_name]);

$route_count_label = t('creator_routes.route_count', ['count' => count($routes)]);

$current_request_uri = (string)($_SERVER['REQUEST_URI'] ?? '');
if ($current_request_uri === '' || !str_starts_with($current_request_uri, ROOT_DIR)) {
    $current_request_uri = ROOT_DIR . 'pages/creator-routes.php';
    if ($creator_public_id !== '') {
        $current_request_uri .= '?creator=' . urlencode($creator_public_id);
    }
}

$login_url = ROOT_DIR . 'login.php?return_to=' . urlencode($current_request_uri);

function formatCreatorRouteDate(string $value): string
{
    try {
        $date = new DateTime($value);
        return current_locale() === 'en' ? $date->format('Y-m-d') : $date->format('d.m.Y');
    } catch (Exception $e) {
        return $value;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="stylesheet" href="../css/bs-custom.css">
    <link rel="stylesheet" href="../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .creator-routes-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .creator-routes-main {
            flex: 1 0 auto;
        }

        .creator-routes-page .site-footer {
            margin-top: auto !important;
        }

        .creator-route-card {
            border: 1px solid rgba(61, 142, 65, 0.22);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #fff;
        }

        .creator-route-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.12);
        }

        .creator-route-card__description {
            min-height: 4.5rem;
        }
    </style>
</head>
<body class="player-dashboard has-site-footer creator-routes-page">
<nav class="navbar navbar-expand-lg admin-navbar" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand admin-navbar__brand" href="<?= htmlspecialchars(ROOT_DIR, ENT_QUOTES, 'UTF-8') ?>">
            <span class="admin-navbar__badge">
                <img src="../images/havu_logo_map.svg" alt="HAVU" class="admin-navbar__badge-image">
            </span>
            <span>
                <?= htmlspecialchars(t('common.app_name'), ENT_QUOTES, 'UTF-8') ?>
                <small class="d-block"><?= htmlspecialchars(t('admin_dashboard.route_sharing'), ENT_QUOTES, 'UTF-8') ?></small>
            </span>
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <?php if (!empty($_SESSION['user_public_id'])): ?>
                <a href="<?= htmlspecialchars(ROOT_DIR . 'pages/player/dashboard.php', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars(t('common.my_profile'), ENT_QUOTES, 'UTF-8') ?>
                </a>
                <?php if (!empty($_SESSION['is_admin'])): ?>
                    <a href="<?= htmlspecialchars(ROOT_DIR . 'pages/admin/dashboard.php', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-light">
                        <i class="bi bi-gear-fill me-1"></i><?= htmlspecialchars(t('common.admin_panel'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endif; ?>
                <?php
                $language_switcher_mode = 'navbar';
                require '../includes/_language_switcher.php';
                ?>
                <a href="<?= htmlspecialchars(ROOT_DIR . 'actions/logout.php', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right me-1"></i><?= htmlspecialchars(t('common.log_out'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php else: ?>
                <?php
                $language_switcher_mode = 'navbar';
                require '../includes/_language_switcher.php';
                ?>
                <a href="<?= htmlspecialchars($login_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-in-right me-1"></i><?= htmlspecialchars(t('common.log_in'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container-fluid pt-4 mb-4 creator-routes-main">
    <div class="player-page-content">
        <div class="admin-feature-panel p-4 bg-secondary-subtle rounded-3 shadow">
            <div class="bg-primary-subtle rounded-3 p-4 mb-4">
        <?php if ($not_found): ?>
            <div class="alert alert-warning mb-0">
                <h1 class="h4 mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars(t('creator_routes.not_found_title'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="mb-3"><?= htmlspecialchars(t('creator_routes.not_found_message'), ENT_QUOTES, 'UTF-8') ?></p>
                <a href="<?= htmlspecialchars(ROOT_DIR, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-1"></i><?= htmlspecialchars(t('creator_routes.back_home'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
                <div>
                    <!--<p class="text-uppercase small fw-semibold text-success mb-2"><?= htmlspecialchars(t('admin_dashboard.route_sharing'), ENT_QUOTES, 'UTF-8') ?></p>-->
                    <h1 class="display-6 fw-bold mb-3"><?= htmlspecialchars(t('creator_routes.heading', ['creator' => $creator_name]), ENT_QUOTES, 'UTF-8') ?></h1>
                    <!--<p class="lead mb-0"><?= htmlspecialchars(t('creator_routes.intro', ['creator' => $creator_name]), ENT_QUOTES, 'UTF-8') ?></p>-->
                </div>
                <span class="badge rounded-pill text-bg-light border fs-6 px-3 py-2 align-self-start align-self-lg-center">
                    <i class="bi bi-signpost-split-fill me-2"></i><?= htmlspecialchars($route_count_label, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        <?php endif; ?>
            </div>

            <?php if (!$not_found): ?>
                <?php if (empty($routes)): ?>
                    <div class="alert alert-info rounded-3">
                        <i class="bi bi-info-circle-fill me-2"></i><?= htmlspecialchars(t('creator_routes.empty_routes'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                        <?php foreach ($routes as $route): ?>
                            <?php
                            $description = trim(strip_tags((string)$route['description']));
                            $description_preview = $description !== ''
                                ? mb_strimwidth($description, 0, 180, '...')
                                : t('creator_routes.description_fallback');
                            ?>
                            <div class="col d-flex">
                                <div class="card creator-route-card h-100 w-100 border-success">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                            <h2 class="h5 mb-0"><?= htmlspecialchars($route['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                            <span class="badge text-bg-success-subtle border border-success-subtle text-success-emphasis">
                                                <?= htmlspecialchars(t('creator_routes.node_count', ['count' => $route['node_count']]), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                        <p class="text-muted creator-route-card__description mb-4"><?= htmlspecialchars($description_preview, ENT_QUOTES, 'UTF-8') ?></p>
                                        <div class="d-flex justify-content-between align-items-center gap-3 mt-auto">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar-event me-1"></i><?= htmlspecialchars(formatCreatorRouteDate($route['created_at']), ENT_QUOTES, 'UTF-8') ?>
                                            </small>
                                            <a href="<?= htmlspecialchars(ROOT_DIR . 'pages/game.php?route=' . urlencode($route['public_id']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success text-white btn-sm">
                                                <i class="bi bi-play-fill me-1"></i><?= htmlspecialchars(t('creator_routes.open_route'), ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once '../includes/_footer.php'; ?>
</body>
</html>