<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/security.class.php');
require_once(__DIR__ . '/../classes/message.class.php');
require_once(__DIR__ . '/../classes/system_admin_messagecenter.class.php');

Security::initSession();

if (empty($_SESSION['user_public_id'])) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message_key' => 'login.session_expired',
    ];
    header('Location: ' . ROOT_DIR . 'login.php');
    exit;
}

if (!Security::isCurrentUserSystemAdmin()) {
    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'System admin access is required.',
    ];
    header('Location: ' . ROOT_DIR . 'pages/admin/dashboard.php');
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$filter = trim($_GET['filter'] ?? 'all');
if (!in_array($filter, ['all', 'unread', 'read', 'resolved'], true)) {
    $filter = 'all';
}

try {
    $result = SystemAdminMessageCenter::getPage($page, $per_page, $filter);
} catch (Exception $e) {
    $result = [
        'messages' => [],
        'total' => 0,
        'page' => 1,
        'per_page' => $per_page,
        'total_pages' => 1,
        'counts' => [
            'all' => 0,
            'unread' => 0,
            'read' => 0,
            'resolved' => 0,
        ],
        'filter' => 'all',
    ];

    $_SESSION['flash_messages'][] = [
        'type' => 'error',
        'code' => 0,
        'message' => 'Failed to load system admin messages.',
    ];
}

$messages = $result['messages'];
$total_pages = $result['total_pages'];
$counts = $result['counts'];
$current_filter = $result['filter'];
$csrf_token = Security::getCsrfToken('system_admin_messages');
$return_to = $_SERVER['REQUEST_URI'] ?? (ROOT_DIR . 'system-admin/messages.php');

function formatSystemAdminMessageDate(?string $date_string): string
{
    if ($date_string === null || trim($date_string) === '') {
        return '-';
    }

    try {
        $value = new DateTime($date_string);
        return current_locale() === 'en' ? $value->format('Y-m-d H:i') : $value->format('d.m.Y H:i');
    } catch (Exception $e) {
        return '-';
    }
}

function feedbackTypeBadgeClass(string $type): string
{
    if ($type === 'bug') {
        return 'bg-danger';
    }
    if ($type === 'feature') {
        return 'bg-info text-dark';
    }
    return 'bg-secondary';
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System admin messages — HAVU</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link rel="stylesheet" href="../css/bs-custom.css">
    <link rel="stylesheet" href="../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="admin-dashboard has-site-footer">
<nav class="navbar navbar-expand-lg admin-navbar" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand admin-navbar__brand" href="../system-admin/">
            <span class="admin-navbar__badge">
                <i class="bi bi-shield-lock-fill"></i>
            </span>
            <span>
                HAVU System Administration
                <small class="d-block">Internal control panel</small>
            </span>
        </a>
        <div class="d-flex flex-column flex-lg-row gap-2 admin-navbar__actions ms-lg-auto">
            <a href="../pages/admin/dashboard.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-gear-fill me-1"></i>Regular admin panel
            </a>
            <a href="../system-admin/" class="btn btn-sm btn-warning">
                <i class="bi bi-arrow-left me-1"></i>Back to dashboard
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid py-4 mx-2">
    <div class="admin-page-content">
        <div class="admin-page-hero p-4 p-lg-5 mb-4">
            <div class="row align-items-start g-4">
                <div class="col-12 col-xl-8">
                    <span class="badge bg-dark text-white mb-3 px-3 py-2">System level</span>
                    <h3 class="mb-3"><i class="bi bi-inbox-fill me-2"></i>Feedback inbox</h3>
                    <p class="lead mb-0">
                        Feedback form submissions are delivered here for all system-admin users.
                        Track unread, read, and resolved items from one shared inbox.
                    </p>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="admin-feature-panel p-4 h-100">
                        <h4 class="mb-3"><i class="bi bi-bar-chart-fill me-2"></i>Current counts</h4>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-1"><span class="badge bg-primary me-2"><?= (int)$counts['all'] ?></span>All</li>
                            <li class="mb-1"><span class="badge bg-warning text-dark me-2"><?= (int)$counts['unread'] ?></span>Unread</li>
                            <li class="mb-1"><span class="badge bg-secondary me-2"><?= (int)$counts['read'] ?></span>Read</li>
                            <li><span class="badge bg-success me-2"><?= (int)$counts['resolved'] ?></span>Resolved</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <?= Message::displayFlashMessages(); ?>

        <div class="d-flex flex-wrap gap-2 mb-3 p-3 admin-page-hero">
            <a href="?filter=all" class="btn btn-sm <?= $current_filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">All (<?= (int)$counts['all'] ?>)</a>
            <a href="?filter=unread" class="btn btn-sm <?= $current_filter === 'unread' ? 'btn-warning text-dark' : 'btn-warning' ?>">Unread (<?= (int)$counts['unread'] ?>)</a>
            <a href="?filter=read" class="btn btn-sm <?= $current_filter === 'read' ? 'btn-secondary' : 'btn-secondary' ?>">Read (<?= (int)$counts['read'] ?>)</a>
            <a href="?filter=resolved" class="btn btn-sm <?= $current_filter === 'resolved' ? 'btn-success' : 'btn-success' ?>">Resolved (<?= (int)$counts['resolved'] ?>)</a>
        </div>

        <?php if (empty($messages)): ?>
            <div class="admin-feature-panel p-5 text-center text-muted">
                <i class="bi bi-inbox" style="font-size: 2.25rem;"></i>
                <p class="mt-2 mb-0">No messages for this filter.</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <?php
                $is_unread = (int)$msg['is_read'] === 0;
                $is_resolved = (int)$msg['is_resolved'] === 1;
                ?>
                <div class="admin-feature-panel p-4 mb-3 <?= $is_unread ? 'border border-warning-subtle' : '' ?>">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <h5 class="mb-0"><?= htmlspecialchars((string)$msg['subject'], ENT_QUOTES, 'UTF-8') ?></h5>
                                <span class="badge <?= htmlspecialchars(feedbackTypeBadgeClass((string)$msg['feedback_type']), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)$msg['feedback_type'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php if ($is_unread): ?>
                                    <span class="badge bg-warning text-dark">Unread</span>
                                <?php endif; ?>
                                <?php if ($is_resolved): ?>
                                    <span class="badge bg-success">Resolved</span>
                                <?php endif; ?>
                            </div>
                            <div class="small text-muted">
                                From <strong><?= htmlspecialchars((string)$msg['sender_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                (<?= htmlspecialchars((string)$msg['sender_email'], ENT_QUOTES, 'UTF-8') ?>)
                            </div>
                            <div class="small text-muted">
                                Page: <?= htmlspecialchars((string)$msg['page_url'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                        <div class="small text-muted text-nowrap">
                            <?= htmlspecialchars(formatSystemAdminMessageDate((string)$msg['created_at']), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <div class="border rounded p-3 bg-light-subtle" style="white-space: pre-wrap;">
                        <?= htmlspecialchars((string)$msg['content'], ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-end mt-3">
                        <form method="post" action="../actions/system-admin-mark-message-read.php" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                            <input type="hidden" name="return_to" value="<?= htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="mark_as" value="<?= $is_unread ? 'read' : 'unread' ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <?= $is_unread ? 'Mark read' : 'Mark unread' ?>
                            </button>
                        </form>

                        <form method="post" action="../actions/system-admin-mark-message-resolved.php" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                            <input type="hidden" name="return_to" value="<?= htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="mark_as" value="<?= $is_resolved ? 'unresolved' : 'resolved' ?>">
                            <button type="submit" class="btn btn-sm <?= $is_resolved ? 'btn-outline-success' : 'btn-success text-white' ?>">
                                <?= $is_resolved ? 'Mark unresolved' : 'Mark resolved' ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($total_pages > 1): ?>
                <nav class="mt-3" aria-label="Messages pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?filter=<?= urlencode($current_filter) ?>&page=<?= $p ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>
</body>
</html>
