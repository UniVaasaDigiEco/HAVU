<?php
// Message Widget - allows players to send messages to route creators
// Tools and Security are already required by the including page

$_mw_route_id = isset($message_route_id) ? (int)$message_route_id : null;
$_mw_route_name = isset($message_route_name) ? htmlspecialchars($message_route_name, ENT_QUOTES, 'UTF-8') : '';
?>

<!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1"
     aria-labelledby="messageModalLabel" aria-hidden="true"
     data-action="<?= htmlspecialchars(ROOT_DIR . 'actions/send-message.php', ENT_QUOTES, 'UTF-8') ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageModalLabel">
                    <i class="bi bi-chat-fill me-2"></i><?= htmlspecialchars(t('message_widget.modal_title'), ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
            </div>
            <div class="modal-body">
                <div id="message-alert" class="alert d-none" role="alert"></div>
                    <form id="message-form"
                        action="<?= htmlspecialchars(ROOT_DIR . 'actions/send-message.php', ENT_QUOTES, 'UTF-8') ?>"
                        method="post"
                        novalidate>
                    <input type="hidden" name="route_id" id="message-route-id" 
                           value="<?= $_mw_route_id !== null ? (int)$_mw_route_id : '' ?>">
                    <input type="hidden" name="recaptcha_token" id="recaptcha-message-token">

                    <?php if ($_mw_route_id !== null): ?>
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong><?= htmlspecialchars(t('message_widget.route_specific'), ENT_QUOTES, 'UTF-8') ?>:</strong>
                            <span id="message-route-info"><?= $_mw_route_name ?></span>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-3 d-none">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong><?= htmlspecialchars(t('message_widget.route_specific'), ENT_QUOTES, 'UTF-8') ?>:</strong>
                            <span id="message-route-info"></span>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="message-title" class="form-label fw-semibold">
                            <?= htmlspecialchars(t('message_widget.title_label'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="message-title" name="title"
                               maxlength="255" required>
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars(t('message_widget.title_help'), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>

                    <div class="mb-3">
                        <label for="message-content" class="form-label fw-semibold">
                            <?= htmlspecialchars(t('message_widget.content_label'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="message-content" name="content"
                                  rows="5" required></textarea>
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars(t('message_widget.content_help'), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>

                    <p class="text-muted small mb-3">
                        <?= htmlspecialchars(t('message_widget.recaptcha_notice'), ENT_QUOTES, 'UTF-8') ?>
                        <a href="https://policies.google.com/privacy" target="_blank" rel="noopener"><?= htmlspecialchars(t('feedback.privacy'), ENT_QUOTES, 'UTF-8') ?></a>
                        <?= htmlspecialchars(t('feedback.terms') !== 'terms' ? ' ' . t('common.and') . ' ' : ' and ') ?>
                        <a href="https://policies.google.com/terms" target="_blank" rel="noopener"><?= htmlspecialchars(t('feedback.terms'), ENT_QUOTES, 'UTF-8') ?></a>.
                    </p>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-warning w-100" id="message-cancel"
                                data-bs-dismiss="modal"><i class="bi bi-x-circle-fill me-1"></i><?= htmlspecialchars(t('common.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
                        <button type="submit" class="btn btn-primary w-100" id="message-submit">
                            <span id="message-submit-text">
                                <i class="bi bi-send me-1"></i><?= htmlspecialchars(t('message_widget.send'), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span id="message-submit-spinner" class="d-none">
                                <span class="spinner-border spinner-border-sm me-1" role="status"
                                      aria-hidden="true"></span><?= htmlspecialchars(t('message_widget.sending'), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>var RECAPTCHA_SITE_KEY = <?= json_encode(RECAPTCHA_SITE_KEY, JSON_HEX_TAG) ?>;</script>
<script>
    window.messageWidgetTranslations = <?= json_encode([
        'cancel' => t('common.cancel'),
        'close' => t('message_widget.close_after_submit'),
        'success' => t('message_widget.success'),
        'genericError' => t('message_widget.generic_error'),
        'networkError' => t('message_widget.network_error'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
<script src="<?= htmlspecialchars(ROOT_DIR, ENT_QUOTES, 'UTF-8') ?>js/message-widget.js"></script>
