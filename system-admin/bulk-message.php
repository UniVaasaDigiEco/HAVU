<?php
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/security.class.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/message.class.php');

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

// Fetch recipient counts for display
$db = Tools::getDb();

$count_active = 0;
$count_all = 0;
$all_users = [];

$stmt = $db->prepare('SELECT public_id, email, full_name, is_active FROM users ORDER BY full_name ASC, email ASC, id ASC');
$stmt->execute();
$stmt->bind_result($user_public_id, $user_email, $user_full_name, $user_is_active);
while ($stmt->fetch()) {
    $all_users[] = [
        'public_id' => (string)$user_public_id,
        'email' => (string)$user_email,
        'full_name' => (string)$user_full_name,
        'is_active' => (int)$user_is_active,
    ];

    $count_all++;
    if ((int)$user_is_active === 1) {
        $count_active++;
    }
}
$stmt->close();

$db->close();

$csrf_token = Security::getCsrfToken('system_admin_bulk_message');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk messaging — HAVU System Administration</title>
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
            <a href="../system-admin/messages.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-inbox-fill me-1"></i>Feedback inbox
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
                    <h3 class="mb-3"><i class="bi bi-envelope-fill me-2"></i>Bulk messaging</h3>
                    <p class="lead mb-0">
                        Send a mass e-mail to users. Use this to share important information or announcements.
                        Sending to all users requires an explicit confirmation to prevent accidental sends.
                    </p>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="admin-feature-panel p-4 h-100">
                        <h4 class="mb-3"><i class="bi bi-people-fill me-2"></i>Current user counts</h4>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-1">
                                <span class="badge bg-success me-2"><?= htmlspecialchars((string)$count_active, ENT_QUOTES, 'UTF-8') ?></span>
                                Active users
                            </li>
                            <li>
                                <span class="badge bg-secondary me-2"><?= htmlspecialchars((string)$count_all, ENT_QUOTES, 'UTF-8') ?></span>
                                Total users (including inactive)
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <?= Message::displayFlashMessages(); ?>

        <div class="admin-feature-panel p-4">
            <form method="post" action="../actions/system-admin-bulk-message.php" id="bulkMessageForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

                <div class="mb-4">
                    <label class="form-label fw-semibold">Recipients</label>
                    <div class="d-flex flex-column gap-2">
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="recipient_scope"
                                id="scope_active"
                                value="active"
                                checked>
                            <label class="form-check-label" for="scope_active">
                                Active users only
                                <span class="badge bg-success ms-1"><?= htmlspecialchars((string)$count_active, ENT_QUOTES, 'UTF-8') ?> recipients</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="recipient_scope"
                                id="scope_all"
                                value="all">
                            <label class="form-check-label" for="scope_all">
                                All users including inactive
                                <span class="badge bg-secondary ms-1"><?= htmlspecialchars((string)$count_all, ENT_QUOTES, 'UTF-8') ?> recipients</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="recipient_scope"
                                id="scope_selected"
                                value="selected">
                            <label class="form-check-label" for="scope_selected">
                                Selected users
                                <span class="badge bg-primary ms-1">Choose manually</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div id="selectedUsersBlock" class="admin-feature-panel p-3 mb-4 d-none">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <label class="form-label fw-semibold mb-0">Select recipients</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllUsersBtn">Select all</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAllUsersBtn">Clear all</button>
                        </div>
                    </div>
                    <div class="small text-muted mb-2">Only checked users will receive this message.</div>
                    <div class="border rounded p-2" style="max-height: 280px; overflow-y: auto;">
                        <?php if (empty($all_users)): ?>
                            <div class="text-muted small p-2">No users available.</div>
                        <?php else: ?>
                            <?php foreach ($all_users as $list_user): ?>
                                <?php
                                $display_name = trim($list_user['full_name']) !== '' ? $list_user['full_name'] : '(No name)';
                                $is_list_user_active = (int)$list_user['is_active'] === 1;
                                ?>
                                <label class="d-flex align-items-center justify-content-between gap-2 px-2 py-1 rounded border mb-2">
                                    <span class="d-flex align-items-center gap-2">
                                        <input
                                            type="checkbox"
                                            class="form-check-input m-0 selected-user-checkbox"
                                            name="selected_user_public_ids[]"
                                            value="<?= htmlspecialchars($list_user['public_id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="small">
                                            <strong><?= htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8') ?></strong>
                                            <span class="text-muted">(<?= htmlspecialchars($list_user['email'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                        </span>
                                    </span>
                                    <?php if ($is_list_user_active): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="subject" class="form-label fw-semibold">Subject</label>
                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        class="form-control"
                        maxlength="200"
                        placeholder="E.g. Important update about HAVU"
                        required>
                </div>

                <div class="mb-4">
                    <label for="body" class="form-label fw-semibold">Message body</label>
                    <textarea
                        id="body"
                        name="body"
                        class="form-control font-monospace"
                        rows="12"
                        maxlength="10000"
                        placeholder="Write your message here. Plain text only — no HTML formatting."
                        required></textarea>
                    <div class="form-text">Plain text. Maximum 10 000 characters.</div>
                </div>

                <div id="confirmationBlock" class="alert alert-danger d-none mb-4" role="alert">
                    <h5 class="alert-heading">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>You are about to email all users
                    </h5>
                    <p class="mb-3">
                        This will send an e-mail to <strong id="confirmRecipientCount"></strong> registered users,
                        including inactive accounts. Make sure the content is correct before sending.
                    </p>
                    <label for="confirm_text" class="form-label fw-semibold">Type <code>SEND ALL</code> to confirm</label>
                    <input
                        type="text"
                        id="confirm_text"
                        name="confirm_text"
                        class="form-control"
                        placeholder="SEND ALL"
                        autocomplete="off">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" id="submitBtn" class="btn btn-primary text-white">
                        <i class="bi bi-send-fill me-1"></i>Send e-mail
                    </button>
                    <a href="../system-admin/" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var countActive = <?= (int)$count_active ?>;
    var countAll    = <?= (int)$count_all ?>;

    var radios = document.querySelectorAll('input[name="recipient_scope"]');
    var block  = document.getElementById('confirmationBlock');
    var selectedUsersBlock = document.getElementById('selectedUsersBlock');
    var form = document.getElementById('bulkMessageForm');
    var countSpan = document.getElementById('confirmRecipientCount');
    var confirmInput = document.getElementById('confirm_text');
    var selectedUserCheckboxes = document.querySelectorAll('.selected-user-checkbox');
    var selectAllUsersBtn = document.getElementById('selectAllUsersBtn');
    var clearAllUsersBtn = document.getElementById('clearAllUsersBtn');

    function selectedScopeEnabled() {
        var scope = document.querySelector('input[name="recipient_scope"]:checked');
        return scope && scope.value === 'selected';
    }

    function update() {
        var scope = document.querySelector('input[name="recipient_scope"]:checked');
        if (!scope) return;

        if (scope.value === 'all') {
            countSpan.textContent = countAll;
            block.classList.remove('d-none');
            confirmInput.required = true;
        } else {
            block.classList.add('d-none');
            confirmInput.required = false;
            confirmInput.value = '';
        }

        if (scope.value === 'selected') {
            selectedUsersBlock.classList.remove('d-none');
        } else {
            selectedUsersBlock.classList.add('d-none');
        }
    }

    if (selectAllUsersBtn) {
        selectAllUsersBtn.addEventListener('click', function () {
            if (!selectedScopeEnabled()) {
                return;
            }
            selectedUserCheckboxes.forEach(function (cb) { cb.checked = true; });
        });
    }

    if (clearAllUsersBtn) {
        clearAllUsersBtn.addEventListener('click', function () {
            selectedUserCheckboxes.forEach(function (cb) { cb.checked = false; });
        });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            if (!selectedScopeEnabled()) {
                return;
            }

            var hasSelected = false;
            selectedUserCheckboxes.forEach(function (cb) {
                if (cb.checked) {
                    hasSelected = true;
                }
            });

            if (!hasSelected) {
                event.preventDefault();
                alert('Select at least one user when using Selected users.');
            }
        });
    }

    radios.forEach(function (r) { r.addEventListener('change', update); });
    update();
})();
</script>

<?php require_once __DIR__ . '/../includes/_footer.php'; ?>
</body>
</html>
