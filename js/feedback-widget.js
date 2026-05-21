(function () {
    'use strict';

    const i18n = window.feedbackWidgetTranslations || {
        cancel: 'Peruuta',
        close: 'Sulje',
        success: 'Kiitos! Viestisi on lähetetty.',
        genericError: 'Jokin meni pieleen. Yritä uudelleen.',
        networkError: 'Verkkovirhe. Tarkista yhteytesi ja yritä uudelleen.'
    };

    const modal     = document.getElementById('feedbackModal');
    const form      = document.getElementById('feedback-form');
    const alertEl   = document.getElementById('feedback-alert');
    const spinner   = document.getElementById('feedback-submit-spinner');
    const btnText   = document.getElementById('feedback-submit-text');
    const submitBtn = document.getElementById('feedback-submit');
    const cancelBtn = document.getElementById('feedback-cancel');
    const typeField = document.getElementById('feedback-type');
    const typeWrapper = typeField ? typeField.closest('.mb-3') : null;
    const titleTextEl = document.getElementById('feedback-modal-title-text');

    let forcedFeedbackType = null;
    const recaptcha = window.grecaptcha;
    const recaptchaSiteKey = window.RECAPTCHA_SITE_KEY;
    const bootstrapApi = window.bootstrap;

    if (!form || !modal) return;

    const endpoint = modal.dataset.action;

    function resetTypeFieldLock() {
        if (!typeField) return;
        forcedFeedbackType = null;
        typeField.required = true;
        if (typeWrapper) {
            typeWrapper.classList.remove('d-none');
            typeWrapper.style.display = '';
        }
        if (titleTextEl) {
            titleTextEl.textContent = i18n.modalTitle || 'Feedback & contact';
        }
    }

    function applyForcedType(type) {
        if (!typeField) return;

        forcedFeedbackType = type;
        typeField.value = type;
        typeField.required = false;

        if (typeWrapper) {
            typeWrapper.classList.add('d-none');
            typeWrapper.style.display = 'none';
        }
    }

    function showAlert(message, isError) {
        alertEl.textContent = message;
        alertEl.className = 'alert ' + (isError ? 'alert-danger' : 'alert-success');
        alertEl.classList.remove('d-none');
    }

    function setLoading(loading) {
        spinner.classList.toggle('d-none', !loading);
        btnText.classList.toggle('d-none', loading);
        submitBtn.disabled = loading;
    }

    modal.addEventListener('hidden.bs.modal', function () {
        alertEl.classList.add('d-none');
        setLoading(false);
        cancelBtn.innerHTML = '<i class="bi bi-x-circle-fill"></i>&nbsp;' + i18n.cancel;
        resetTypeFieldLock();
    });

    if (typeField) {
        typeField.addEventListener('change', function () {
            if (forcedFeedbackType !== null) {
                typeField.value = forcedFeedbackType;
            }
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        setLoading(true);

        if (!recaptcha || !recaptchaSiteKey) {
            setLoading(false);
            showAlert(i18n.genericError, true);
            return;
        }

        recaptcha.ready(function () {
            recaptcha.execute(recaptchaSiteKey, { action: 'feedback' })
                .then(function (token) {
                    document.getElementById('recaptcha-token').value = token;

                    fetch(endpoint, { method: 'POST', body: new FormData(form) })
                        .then(function (res) { return res.json(); })
                        .then(function (json) {
                            setLoading(false);
                            if (json.ok) {
                                showAlert(i18n.success, false);
                                form.reset();
                                form.classList.remove('was-validated');
                                cancelBtn.innerHTML = '<i class="bi bi-x-circle-fill"></i>&nbsp;' + i18n.close;
                            } else {
                                showAlert(json.error || i18n.genericError, true);
                            }
                        })
                        .catch(function () {
                            setLoading(false);
                            showAlert(i18n.networkError, true);
                        });
                });
        });
    });

    // Exposed for the game page inline trigger
    window.openFeedbackModal = function (forcedType) {
        if (typeField) {
            resetTypeFieldLock();
            if (forcedType === 'bug') {
                applyForcedType('bug');
                if (titleTextEl) {
                    titleTextEl.textContent = i18n.bugTitle || 'Report a bug';
                }
            }
        }
        if (!bootstrapApi || !bootstrapApi.Modal) {
            return;
        }
        bootstrapApi.Modal.getOrCreateInstance(modal).show();
    };
}());
