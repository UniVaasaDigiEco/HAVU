<?php
require_once(__DIR__ . '/../../config/constants.php');
require_once(__DIR__ . '/../../classes/tools.class.php');
require_once(__DIR__ . '/../../classes/security.class.php');
require_once(__DIR__ . '/../../classes/message.class.php');
require_once(__DIR__ . '/../../classes/messagecenter.class.php');
require_once(__DIR__ . '/../../classes/user.class.php');
require_once(__DIR__ . '/../../includes/i18n.php');

Security::initSession();

// Check if user is logged in
if (empty($_SESSION['user_public_id'])) {
    header('Location: ' . ROOT_DIR . 'login.php');
    exit;
}

// Get current user's numeric ID
try {
    $current_user_id = Tools::getUserIdByPublicId($_SESSION['user_public_id']);
    $current_user = new User($current_user_id);
} catch (Exception $e) {
    header('Location: ' . ROOT_DIR . 'login.php');
    exit;
}

// Check if user is a route creator (has created at least one route)
// or is an admin (user_type = 0)
$is_creator_or_admin = false;
if ($current_user->getUserType() == 0) {
    $is_creator_or_admin = true;
} else {
    $db = Tools::getDb();
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM routes WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param('s', $_SESSION['user_public_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        if ($row['cnt'] > 0) {
            $is_creator_or_admin = true;
        }
    }
    $db->close();
}

if (!$is_creator_or_admin) {
    header('Location: ' . ROOT_DIR . 'pages/player/dashboard.php');
    exit;
}

// Get messages for current user
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;
$filter_read = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$filter_options = ['limit' => $per_page, 'offset' => $offset];
if ($filter_read === 'unread') {
    $filter_options['is_read'] = 0;
} elseif ($filter_read === 'read') {
    $filter_options['is_read'] = 1;
}

try {
    $messages = MessageCenter::getForRecipient($current_user_id, $filter_options);
    $total_messages = count(MessageCenter::getForRecipient($current_user_id, []));
    $unread_count = MessageCenter::getUnreadCount($current_user_id);
} catch (Exception $e) {
    $messages = [];
    $total_messages = 0;
    $unread_count = 0;
}

$total_pages = ceil($total_messages / $per_page);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('common.app_name') . ' - ' . t('common.messages'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/x-icon" href="../../favicon.ico">
    <link rel="stylesheet" href="../../css/bs-custom.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
<body class="admin-dashboard has-site-footer">
    <?php
    $admin_nav_current = 'messages';
    require_once __DIR__ . '/../../includes/_admin_nav.php';
    ?>

    <main class="container-fluid py-4">
        <div class="admin-page-content">
            <div id="messages-center" class="admin-feature-panel p-4 bg-secondary-subtle rounded-3 shadow">
                <div class="mb-4">
                    <h3><i class="bi bi-inbox-fill me-2"></i><?= htmlspecialchars(t('messages_center.heading'), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="lead mb-0"><?= htmlspecialchars(t('messages_center.intro'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <?= Message::displayFlashMessages() ?>

                <?php if ($unread_count > 0): ?>
                    <div class="alert alert-light border border-primary-subtle text-primary-emphasis mb-4">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?= htmlspecialchars(t('messages_center.unread_count', ['count' => $unread_count]), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <div class="d-flex flex-wrap gap-2 mb-4" role="group" aria-label="<?= htmlspecialchars(t('common.search'), ENT_QUOTES, 'UTF-8') ?>">
                    <a href="?filter=all" class="btn btn-sm <?= $filter_read === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <i class="bi bi-funnel me-1"></i><?= htmlspecialchars(t('messages_center.filter_all'), ENT_QUOTES, 'UTF-8') ?>
                        (<?= $total_messages ?>)
                    </a>
                    <a href="?filter=unread" class="btn btn-sm <?= $filter_read === 'unread' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <i class="bi bi-envelope-fill me-1"></i><?= htmlspecialchars(t('messages_center.filter_unread'), ENT_QUOTES, 'UTF-8') ?>
                        (<?= $unread_count ?>)
                    </a>
                    <a href="?filter=read" class="btn btn-sm <?= $filter_read === 'read' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <i class="bi bi-envelope-open-fill me-1"></i><?= htmlspecialchars(t('messages_center.filter_read'), ENT_QUOTES, 'UTF-8') ?>
                        (<?= $total_messages - $unread_count ?>)
                    </a>
                </div>

                <!-- Messages List -->
                <?php if (empty($messages)): ?>
                    <div class="alert alert-info text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars(t('messages_center.no_messages'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-12">
                            <?php foreach ($messages as $msg): ?>
                                <?php
                                $sender_name = 'Anonymous User';
                                $sender_email = 'N/A';
                                if ($msg['sender_user_id'] !== null) {
                                    try {
                                        $sender = new User($msg['sender_user_id']);
                                        $sender_name = $sender->getFullName();
                                        $sender_email = $sender->getEmail();
                                    } catch (Exception $e) {
                                        $sender_name = 'Unknown User';
                                    }
                                }

                                $route_name = null;
                                if ($msg['route_id'] !== null) {
                                    try {
                                        $db = Tools::getDb();
                                        $stmt = $db->prepare("SELECT title FROM routes WHERE id = ?");
                                        if ($stmt) {
                                            $stmt->bind_param('i', $msg['route_id']);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            if ($result->num_rows > 0) {
                                                $route_row = $result->fetch_assoc();
                                                $route_name = $route_row['title'];
                                            }
                                            $stmt->close();
                                        }
                                        $db->close();
                                    } catch (Exception $e) {
                                        // Non-fatal
                                    }
                                }

                                $is_unread = $msg['is_read'] == 0;
                                $card_class = $is_unread ? 'border-left-primary bg-light-subtle' : '';

                                $raw_title = trim((string)($msg['title'] ?? ''));
                                if ($raw_title === '' || $raw_title === '0') {
                                    $content_preview = trim((string)($msg['content'] ?? ''));
                                    if ($content_preview !== '') {
                                        $display_title = mb_strimwidth($content_preview, 0, 80, '...');
                                    } else {
                                        $display_title = t('messages_center.general_message');
                                    }
                                } else {
                                    $display_title = $raw_title;
                                }
                                ?>
                                <div class="card mb-3 <?= htmlspecialchars($card_class, ENT_QUOTES, 'UTF-8') ?>" style="<?= $is_unread ? 'border-left: 4px solid #0d6efd;' : '' ?>">
                                    <div class="card-header d-flex justify-content-between align-items-start pb-2">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($display_title, ENT_QUOTES, 'UTF-8') ?></h6>
                                                <?php if ($is_unread): ?>
                                                    <span class="badge bg-primary"><?= htmlspecialchars(t('common.new'), ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <strong><?= htmlspecialchars(t('messages_center.from'), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars($sender_name, ENT_QUOTES, 'UTF-8') ?>
                                                <?php if ($sender_email !== 'N/A'): ?>
                                                    (<a href="mailto:<?= htmlspecialchars($sender_email, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($sender_email, ENT_QUOTES, 'UTF-8') ?></a>)
                                                <?php endif; ?>
                                            </small>
                                            <?php if ($route_name): ?>
                                                <div class="mt-1">
                                                    <small class="text-muted badge bg-info text-dark">
                                                        <i class="bi bi-map me-1"></i><?= htmlspecialchars($route_name, ENT_QUOTES, 'UTF-8') ?>
                                                    </small>
                                                </div>
                                            <?php else: ?>
                                                <div class="mt-1">
                                                    <small class="text-muted badge bg-secondary">
                                                        <i class="bi bi-chat me-1"></i><?= htmlspecialchars(t('messages_center.general_message'), ENT_QUOTES, 'UTF-8') ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted text-nowrap ms-2">
                                            <?= htmlspecialchars(date('d.m.Y H:i', strtotime($msg['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                        </small>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text" style="white-space: pre-wrap;">
                                            <?= htmlspecialchars($msg['content'], ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-transparent border-top d-flex gap-2 justify-content-end">
                                        <?php if ($is_unread): ?>
                                            <button class="btn btn-sm btn-outline-secondary mark-read-btn" data-message-id="<?= (int)$msg['id'] ?>" data-action="read">
                                                <i class="bi bi-envelope-open-fill me-1"></i><?= htmlspecialchars(t('messages_center.mark_read'), ENT_QUOTES, 'UTF-8') ?>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary mark-read-btn" data-message-id="<?= (int)$msg['id'] ?>" data-action="unread">
                                                <i class="bi bi-envelope-fill me-1"></i><?= htmlspecialchars(t('messages_center.mark_unread'), ENT_QUOTES, 'UTF-8') ?>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger delete-msg-btn" data-message-id="<?= (int)$msg['id'] ?>">
                                            <i class="bi bi-trash me-1"></i><?= htmlspecialchars(t('common.delete'), ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&filter=<?= htmlspecialchars($filter_read, ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="bi bi-chevron-double-left me-1"></i><?= htmlspecialchars(t('common.first'), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&filter=<?= htmlspecialchars($filter_read, ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="bi bi-chevron-left me-1"></i><?= htmlspecialchars(t('common.previous'), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <li class="page-item disabled">
                                    <span class="page-link"><?= htmlspecialchars(t('common.page', ['current' => $page, 'total' => $total_pages]), ENT_QUOTES, 'UTF-8') ?></span>
                                </li>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&filter=<?= htmlspecialchars($filter_read, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars(t('common.next'), ENT_QUOTES, 'UTF-8') ?><i class="bi bi-chevron-right ms-1"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $total_pages ?>&filter=<?= htmlspecialchars($filter_read, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars(t('common.last'), ENT_QUOTES, 'UTF-8') ?><i class="bi bi-chevron-double-right ms-1"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../../includes/_footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mark as read/unread buttons
            document.querySelectorAll('.mark-read-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const messageId = this.dataset.messageId;
                    const action = this.dataset.action;

                    const payload = new URLSearchParams();
                    payload.set('message_id', messageId);
                    payload.set('action', action);

                    fetch('<?= htmlspecialchars(ROOT_DIR, ENT_QUOTES, 'UTF-8') ?>actions/mark-message-read.php', {
                        method: 'POST',
                        body: payload.toString(),
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    })
                    .then(res => res.json())
                    .then(json => {
                        if (json.ok) {
                            location.reload();
                        } else {
                            alert('Error: ' + (json.error || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        alert('Network error: ' + err.message);
                    });
                });
            });

            // Delete button
            document.querySelectorAll('.delete-msg-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!confirm('<?= htmlspecialchars(t('messages_center.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>')) return;

                    const messageId = this.dataset.messageId;
                    const payload = new URLSearchParams();
                    payload.set('message_id', messageId);

                    fetch('<?= htmlspecialchars(ROOT_DIR, ENT_QUOTES, 'UTF-8') ?>actions/delete-message.php', {
                        method: 'POST',
                        body: payload.toString(),
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    })
                    .then(res => res.json())
                    .then(json => {
                        if (json.ok) {
                            location.reload();
                        } else {
                            alert('Error: ' + (json.error || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        alert('Network error: ' + err.message);
                    });
                });
            });
        });
    </script>
</body>
</html>
