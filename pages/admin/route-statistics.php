<?php
require_once('../../config/constants.php');
require_once('../../classes/tools.class.php');
require_once('../../classes/security.class.php');
require_once('../../classes/message.class.php');

Security::initSession();

try {
    $route_statistics = Tools::getRouteStatisticsForCreator($_SESSION['user_public_id']);
} catch (Exception $e) {
    die("Error fetching route statistics: " . $e->getMessage());
}

$summary_stats = $route_statistics['summary'];
$route_stats = $route_statistics['routes'];

/**
 * Format a number for statistics cards/tables.
 *
 * @param float $value
 * @param int $decimals
 * @return string
 */
function formatStatisticsNumber(float $value, int $decimals = 0): string
{
    $decimal_separator = current_locale() === 'en' ? '.' : ',';
    return number_format($value, $decimals, $decimal_separator, ' ');
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('admin_route_statistics.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/x-icon" href="../../favicon.ico">
    <link rel="stylesheet" href="../../css/bs-custom.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
<body class="admin-dashboard has-site-footer">
<?php
$admin_nav_current = 'route-statistics';
require_once '../../includes/_admin_nav.php';
?>
<div class="container-fluid py-4">
    <div class="admin-page-content">
        <div id="route-statistics" class="admin-feature-panel p-4 bg-secondary-subtle rounded-3 shadow">
            <div class="mb-4">
                <h3><i class="bi bi-bar-chart-line-fill me-2"></i><?= htmlspecialchars(t('admin_route_statistics.heading'), ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="lead mb-0"><?= htmlspecialchars(t('admin_route_statistics.intro'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?= Message::displayFlashMessages() ?>

            <?php if (empty($route_stats)): ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle-fill me-2"></i><?= htmlspecialchars(t('admin_route_statistics.empty_routes'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php else: ?>
                <?php
                $summary_cards = [
                    [
                        'icon' => 'bi bi-signpost-split-fill',
                        'label' => t('admin_route_statistics.total_routes'),
                        'value' => formatStatisticsNumber((float)$summary_stats['total_routes']),
                    ],
                    [
                        'icon' => 'bi bi-trophy-fill',
                        'label' => t('admin_route_statistics.total_completions'),
                        'value' => formatStatisticsNumber((float)$summary_stats['total_completions']),
                    ],
                    [
                        'icon' => 'bi bi-geo-alt-fill',
                        'label' => t('admin_route_statistics.total_nodes_collected'),
                        'value' => formatStatisticsNumber((float)$summary_stats['total_nodes_collected']),
                    ],
                ];
                ?>

                <div class="alert alert-light border border-success-subtle text-success-emphasis mb-4">
                    <i class="bi bi-shield-check me-2"></i><?= htmlspecialchars(t('admin_route_statistics.anonymised_notice'), ENT_QUOTES, 'UTF-8') ?>
                </div>

                <div class="row g-3 mb-4">
                    <?php foreach ($summary_cards as $card): ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="admin-stat-card h-100">
                                <div class="admin-stat-card__icon">
                                    <i class="<?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                </div>
                                <div class="admin-stat-card__body">
                                    <p class="admin-stat-card__label mb-1"><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="admin-stat-card__value mb-0"><?= htmlspecialchars($card['value'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h4 class="mb-0"><?= htmlspecialchars(t('admin_route_statistics.routes_heading'), ENT_QUOTES, 'UTF-8') ?></h4>
                    <span class="badge rounded-pill text-bg-light border"><?= htmlspecialchars(t('admin_route_statistics.includes_private_routes'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle admin-stats-table">
                        <thead>
                        <tr>
                            <th><?= htmlspecialchars(t('admin_route_statistics.table_route'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('admin_route_statistics.table_total_nodes'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('admin_route_statistics.table_started'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('admin_route_statistics.table_finished'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('admin_route_statistics.table_average_nodes'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars(t('admin_route_statistics.table_completion_rate'), ENT_QUOTES, 'UTF-8') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($route_stats as $route_stat): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($route_stat['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(formatStatisticsNumber((float)$route_stat['total_nodes']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(formatStatisticsNumber((float)$route_stat['started_count']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(formatStatisticsNumber((float)$route_stat['finished_count']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(formatStatisticsNumber((float)$route_stat['avg_nodes_collected'], 1), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(formatStatisticsNumber((float)$route_stat['completion_rate'], 1), ENT_QUOTES, 'UTF-8') ?> %</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once '../../includes/_footer.php'; ?>
</body>
</html>
