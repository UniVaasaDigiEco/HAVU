<footer class="site-footer">
    <div class="container-fluid site-footer__inner">
        <div class="site-footer__content">
            <div class="site-footer__summary">
                <h2 class="site-footer__title"><?= htmlspecialchars(t('common.app_name'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="site-footer__text mt-2 mb-0">
                    <a href="<?= htmlspecialchars(ROOT_DIR . 'privacy/privacy_notice_' . current_locale() . '.pdf', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                        <?= htmlspecialchars(t('feedback.privacy'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </p>
            </div>
            <?php if (!empty($_SESSION['user_public_id'])): ?>
                <div class="site-footer__actions">
                    <button type="button"
                            class="btn btn-primary site-footer__feedback-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#feedbackModal"
                            title="<?= htmlspecialchars(t('feedback.button_title'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-chat-dots-fill me-1"></i><?= htmlspecialchars(t('feedback.button_label'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</footer>
<?php if (!empty($_SESSION['user_public_id'])) require __DIR__ . '/_feedback_widget.php'; ?>
<?php require __DIR__ . '/_tos_modal.php'; ?>
