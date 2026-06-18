<?php
/**
 * TOS acceptance modal for existing users who haven't accepted yet.
 * Included from _footer.php — only renders when user is logged in and tos_accepted = 0.
 */
if (empty($_SESSION['user_public_id'])) {
    return;
}

try {
    $_tos_user = Tools::getUserWithPublicId($_SESSION['user_public_id']);
} catch (Exception $e) {
    return;
}

if ($_tos_user->getTosAccepted() === 1) {
    unset($_tos_user);
    return;
}
unset($_tos_user);

$_tos_locale_map = ['fi' => 'tos_fi', 'en' => 'tos_en', 'sv' => 'tos_sv'];
$_tos_file = $_tos_locale_map[current_locale()] ?? 'tos_fi';
$_tos_url = ROOT_DIR . 'tos/' . $_tos_file . '.pdf';
$_tos_link = '<a href="' . htmlspecialchars($_tos_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">'
    . htmlspecialchars(t('tos_modal.tos_link_text'), ENT_QUOTES, 'UTF-8') . '</a>';
?>

<div class="modal fade" id="tosAcceptModal" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false"
     aria-labelledby="tosAcceptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tosAcceptModalLabel">
                    <i class="bi bi-file-earmark-text me-2"></i><?= htmlspecialchars(t('tos_modal.title'), ENT_QUOTES, 'UTF-8') ?>
                </h5>
            </div>
            <div class="modal-body">
                <p><?= str_replace(':link', $_tos_link, htmlspecialchars(t('tos_modal.body'), ENT_QUOTES, 'UTF-8')) ?></p>
                <p class="text-muted small mb-0"><?= htmlspecialchars(t('tos_modal.contact_notice'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="modal-footer">
                <a href="<?= htmlspecialchars(ROOT_DIR . 'actions/logout.php', ENT_QUOTES, 'UTF-8') ?>"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-box-arrow-right me-1"></i><?= htmlspecialchars(t('tos_modal.decline'), ENT_QUOTES, 'UTF-8') ?>
                </a>
                <form method="POST" action="<?= htmlspecialchars(ROOT_DIR . 'actions/accept-tos.php', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-success text-white">
                        <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars(t('tos_modal.accept'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('tosAcceptModal');
    if (el) new bootstrap.Modal(el).show();
});
</script>
