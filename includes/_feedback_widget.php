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

<?php if (empty($feedback_widget_no_float)): ?>
<button type="button"
        class="btn btn-primary shadow feedback-float-btn"
        id="feedback-float-btn"
        data-bs-toggle="modal"
        data-bs-target="#feedbackModal"
        title="Lähetä palaute">
    <i class="bi bi-chat-dots-fill me-1"></i>Palaute
</button>
<?php endif; ?>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1"
     style="z-index: 2001;"
     aria-labelledby="feedbackModalLabel" aria-hidden="true"
     data-action="<?= htmlspecialchars(ROOT_DIR . 'actions/submit-feedback.php', ENT_QUOTES, 'UTF-8') ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel">
                    <i class="bi bi-chat-dots-fill me-2"></i>Palaute &amp; yhteydenotto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Sulje"></button>
            </div>
            <div class="modal-body">
                <div id="feedback-alert" class="alert d-none" role="alert"></div>
                <form id="feedback-form" novalidate>
                    <input type="hidden" name="page_url"
                           value="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="recaptcha_token" id="recaptcha-token">

                    <div class="mb-3">
                        <label for="feedback-type" class="form-label fw-semibold">
                            Tyyppi <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="feedback-type" name="type" required>
                            <option value="" disabled selected>Valitse...</option>
                            <option value="contact">Ota yhteyttä</option>
                            <option value="bug">Ilmoita virheestä</option>
                            <option value="feature">Ehdota ominaisuutta</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="feedback-name" class="form-label fw-semibold">
                            Nimi <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="feedback-name" name="name"
                               value="<?= htmlspecialchars($_fw_name, ENT_QUOTES, 'UTF-8') ?>"
                               maxlength="100" required>
                    </div>

                    <div class="mb-3">
                        <label for="feedback-email" class="form-label fw-semibold">
                            Sähköposti <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control" id="feedback-email" name="email"
                               value="<?= htmlspecialchars($_fw_email, ENT_QUOTES, 'UTF-8') ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="feedback-message" class="form-label fw-semibold">
                            Viesti <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="feedback-message" name="message"
                                  rows="4" required></textarea>
                    </div>

                    <p class="text-muted small mb-3">
                        Tämä lomake on suojattu reCAPTCHA:lla.
                        <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Tietosuoja</a>
                        ja
                        <a href="https://policies.google.com/terms" target="_blank" rel="noopener">käyttöehdot</a>.
                    </p>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-warning w-100" id="feedback-cancel"
                                data-bs-dismiss="modal"><i class="bi bi-x-circle-fill"></i>&nbsp;Peruuta</button>
                        <button type="submit" class="btn btn-primary w-100" id="feedback-submit">
                            <span id="feedback-submit-text">
                                <i class="bi bi-send me-1"></i>Lähetä
                            </span>
                            <span id="feedback-submit-spinner" class="d-none">
                                <span class="spinner-border spinner-border-sm me-1" role="status"
                                      aria-hidden="true"></span>Lähetetään…
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>const RECAPTCHA_SITE_KEY = <?= json_encode(RECAPTCHA_SITE_KEY, JSON_HEX_TAG) ?>;</script>
<script src="<?= htmlspecialchars(ROOT_DIR, ENT_QUOTES, 'UTF-8') ?>js/feedback-widget.js"></script>
