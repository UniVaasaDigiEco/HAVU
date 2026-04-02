<?php
require_once('../../config/constants.php');
require_once('../../classes/tools.class.php');
require_once('../../classes/security.class.php');

Security::initSession();

if (empty($_SESSION['user_public_id'])) {
    header('Location: ../../login.php');
    exit;
}

try {
    $user_id = Tools::getUserIdByPublicId($_SESSION['user_public_id']);
    $user    = Tools::getUserWithPublicId($_SESSION['user_public_id']);
} catch (Exception $e) {
    session_destroy();
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
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAVU Gamification - Oma profiili</title>
    <link rel="stylesheet" href="../../css/bs-custom.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-primary" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="../../index.php">
            <img src="../../images/havu_logo.png" alt="HAVU" height="30" class="me-2">
            HAVU Gamification
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a href="../routes.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-map me-1"></i>Kaikki reitit
            </a>
            <?php if (!empty($_SESSION['is_admin'])): ?>
                <a href="../admin/dashboard.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-gear-fill me-1"></i>Hallintapaneeli
                </a>
            <?php endif; ?>
            <a href="../../actions/logout.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-box-arrow-right me-1"></i>Kirjaudu ulos
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">

    <!-- Profile header -->
    <div class="d-flex align-items-center gap-3 mb-5">
        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
             style="width:56px;height:56px;font-size:1.5rem;">
            <i class="bi bi-person-fill"></i>
        </div>
        <div>
            <h2 class="mb-0"><?= htmlspecialchars($user->getFullName(), ENT_QUOTES, 'UTF-8') ?></h2>
            <span class="text-muted small"><?= htmlspecialchars($user->getEmail(), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="ms-auto text-end">
            <span class="badge bg-success fs-6 px-3 py-2">
                <i class="bi bi-check-circle-fill me-1"></i>
                <?= count($completed_routes) ?> suoritettua reittiä
            </span>
        </div>
    </div>

    <!-- In progress -->
    <?php if (!empty($in_progress)): ?>
        <h4 class="mb-3"><i class="bi bi-hourglass-split text-warning me-2"></i>Kesken olevat reitit</h4>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-5">
            <?php foreach ($in_progress as $r):
                $pct = $r['node_count'] > 0 ? round($r['visited'] / $r['node_count'] * 100) : 0;
            ?>
                <div class="col">
                    <div class="card h-100 border-warning shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></h6>
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Rasteja käyty</span>
                                <span><?= $r['visited'] ?>/<?= $r['node_count'] ?></span>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pb-3">
                            <a href="../game.php?route=<?= htmlspecialchars($r['public_id'], ENT_QUOTES, 'UTF-8') ?>"
                               class="btn btn-warning w-100">
                                <i class="bi bi-play-fill me-1"></i>Jatka reittiä
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Completed routes -->
    <?php if (!empty($completed_routes)): ?>
        <h4 class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Suoritetut reitit</h4>
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
                                Suoritettu <?= date('d.m.Y', strtotime($r['completed_at'])) ?>
                            </small>
                        </div>
                        <div class="card-footer bg-transparent border-0 pb-3">
                            <a href="../game.php?route=<?= htmlspecialchars($r['public_id'], ENT_QUOTES, 'UTF-8') ?>"
                               class="btn btn-outline-success w-100 btn-sm">
                                <i class="bi bi-arrow-repeat me-1"></i>Pelaa uudelleen
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Available routes -->
    <h4 class="mb-3"><i class="bi bi-map text-primary me-2"></i>Saatavilla olevat reitit</h4>
    <?php if (empty($available_routes)): ?>
        <div class="text-center py-4 text-muted">
            <i class="bi bi-trophy-fill text-warning" style="font-size: 3rem;"></i>
            <p class="mt-3 fw-bold">Olet suorittanut kaikki reitit!</p>
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
                                <i class="bi bi-geo-alt-fill text-primary me-1"></i><?= $r['node_count'] ?> rastia
                            </small>
                        </div>
                        <div class="card-footer bg-transparent border-0 pb-3">
                            <a href="../game.php?route=<?= htmlspecialchars($r['public_id'], ENT_QUOTES, 'UTF-8') ?>"
                               class="btn btn-primary w-100">
                                <i class="bi bi-play-fill me-1"></i>Aloita reitti
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
