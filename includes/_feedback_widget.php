<?php
// Fetch logged-in user's name/email for pre-filling.
// Tools and Security are already required by the including page.
$_fw_name  = '';
$_fw_email = '';
if (!empty($_SESSION['user_public_id'])) {
    try {
        $_fw_user  = Tools::getUserWithPublicId($_SESSION['user_public_id']);
        $_fw_name  = $_fw_user->getFullName();
        $_fw_email = $_fw_user->getEmail();
        unset($_fw_user);
    } catch (Exception $e) {
        // Non-fatal — leave fields empty
    }
}
?>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1"
     aria-labelledby="feedbackModalLabel" aria-hidden="true"
     data-action="<?= htmlspecialchars(ROOT_DIR . 'actions/submit-feedback.php', ENT_QUOTES, 'UTF-8') ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel">
                    <i class="bi bi-chat-dots-fill me-2"></i><?= htmlspecialchars(t('feedback.modal_title'), ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
            </div>
            <div class="modal-body">
                <div id="feedback-alert" class="alert d-none" role="alert"></div>
                <form id="feedback-form" novalidate>
                    <input type="hidden" name="page_url"
                           value="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="recaptcha_token" id="recaptcha-token">

                    <div class="mb-3">
                        <label for="feedback-type" class="form-label fw-semibold">
                            <?= htmlspecialchars(t('feedback.type_label'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="feedback-type" name="type" required>
                            <option value="" disabled selected><?= htmlspecialchars(t('feedback.type_placeholder'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="contact"><?= htmlspecialchars(t('feedback.type_contact'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="bug"><?= htmlspecialchars(t('feedback.type_bug'), ENT_QUOTES, 'UTF-8') ?></option>
                            <!--<option value="feature"><?= htmlspecialchars(t('feedback.type_feature'), ENT_QUOTES, 'UTF-8') ?></option>-->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="feedback-name" class="form-label fw-semibold">
                            <?= htmlspecialchars(t('feedback.name_label'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="feedback-name" name="name"
                               value="<?= htmlspecialchars($_fw_name, ENT_QUOTES, 'UTF-8') ?>"
                               maxlength="100" required>
                    </div>

                    <div class="mb-3">
                        <label for="feedback-email" class="form-label fw-semibold">
                            <?= htmlspecialchars(t('feedback.email_label'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control" id="feedback-email" name="email"
                               value="<?= htmlspecialchars($_fw_email, ENT_QUOTES, 'UTF-8') ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="feedback-message" class="form-label fw-semibold">
                            <?= htmlspecialchars(t('feedback.message_label'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="feedback-message" name="message"
                                  rows="4" required></textarea>
                    </div>

                    <p class="text-muted small mb-3">
                        <?= htmlspecialchars(t('feedback.recaptcha_notice'), ENT_QUOTES, 'UTF-8') ?>
                        <a href="https://policies.google.com/privacy" target="_blank" rel="noopener"><?= htmlspecialchars(t('feedback.privacy'), ENT_QUOTES, 'UTF-8') ?></a>
                        ja
                        <a href="https://policies.google.com/terms" target="_blank" rel="noopener"><?= htmlspecialchars(t('feedback.terms'), ENT_QUOTES, 'UTF-8') ?></a>.
                    </p>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-warning w-100" id="feedback-cancel"
                                data-bs-dismiss="modal"><i class="bi bi-x-circle-fill"></i>&nbsp;<?= htmlspecialchars(t('common.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                        <button type="submit" class="btn btn-primary w-100" id="feedback-submit">
                            <span id="feedback-submit-text">
                                <i class="bi bi-send me-1"></i><?= htmlspecialchars(t('feedback.submit'), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span id="feedback-submit-spinner" class="d-none">
                                <span class="spinner-border spinner-border-sm me-1" role="status"
                                      aria-hidden="true"></span><?= htmlspecialchars(t('feedback.sending'), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>const RECAPTCHA_SITE_KEY = <?= json_encode(RECAPTCHA_SITE_KEY, JSON_HEX_TAG) ?>;</script>
<script>
    window.feedbackWidgetTranslations = <?= json_encode([
        'cancel' => t('common.cancel'),
        'close' => t('feedback.close_after_submit'),
        'success' => t('feedback.success'),
        'genericError' => t('feedback.generic_error'),
        'networkError' => t('feedback.network_error'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
<script src="<?= htmlspecialchars(ROOT_DIR, ENT_QUOTES, 'UTF-8') ?>js/feedback-widget.js"></script>
