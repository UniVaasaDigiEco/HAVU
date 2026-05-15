(function () {
    'use strict';

    const i18n = window.messageWidgetTranslations || {
        cancel: 'Peruuta',
        close: 'Sulje',
        success: 'Kiitos! Viestisi on lähetetty.',
        genericError: 'Jokin meni pieleen. Yritä uudelleen.',
        networkError: 'Verkkovirhe. Tarkista yhteytesi ja yritä uudelleen.'
    };

    const modal     = document.getElementById('messageModal');
    const form      = document.getElementById('message-form');
    const alertEl   = document.getElementById('message-alert');
    const spinner   = document.getElementById('message-submit-spinner');
    const btnText   = document.getElementById('message-submit-text');
    const submitBtn = document.getElementById('message-submit');
    const cancelBtn = document.getElementById('message-cancel');

    if (!form || !modal) return;

    const endpoint = modal.dataset.action || form.getAttribute('action') || '';

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
        cancelBtn.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i>' + i18n.cancel;
        form.reset();
        form.classList.remove('was-validated');
        
        // Reset route info display
        const routeInfoEl = document.getElementById('message-route-info');
        if (routeInfoEl) {
            routeInfoEl.textContent = '';
            routeInfoEl.parentElement.classList.add('d-none');
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        setLoading(true);

        grecaptcha.ready(function () {
            grecaptcha.execute(RECAPTCHA_SITE_KEY, { action: 'message' })
                .then(function (token) {
                    document.getElementById('recaptcha-message-token').value = token;

                    fetch(endpoint, { method: 'POST', body: new FormData(form) })
                        .then(function (res) { return res.json(); })
                        .then(function (json) {
                            setLoading(false);
                            if (json.ok) {
                                showAlert(i18n.success, false);
                                form.reset();
                                form.classList.remove('was-validated');
                                cancelBtn.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i>' + i18n.close;
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

    // Exposed for game page inline trigger
    window.openMessageModal = function (routeId, routeName) {
        if (routeId !== undefined && routeId !== null) {
            document.getElementById('message-route-id').value = routeId;
        }
        if (routeName !== undefined && routeName !== null) {
            const routeInfoEl = document.getElementById('message-route-info');
            if (routeInfoEl) {
                routeInfoEl.textContent = routeName;
                routeInfoEl.parentElement.classList.remove('d-none');
            }
        } else {
            const routeInfoEl = document.getElementById('message-route-info');
            if (routeInfoEl) {
                routeInfoEl.parentElement.classList.add('d-none');
            }
        }
        bootstrap.Modal.getOrCreateInstance(modal).show();
    };
}());
