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
    const recaptchaSiteKey = window.RECAPTCHA_SITE_KEY;
    const bootstrapApi = window.bootstrap;
    let recaptchaLoadPromise = null;

    if (!form || !modal) return;

    const endpoint = modal.dataset.action || form.getAttribute('action') || '';

    function getRecaptchaApi() {
        return (window.grecaptcha && typeof window.grecaptcha.ready === 'function')
            ? window.grecaptcha
            : null;
    }

    function ensureRecaptchaApi() {
        const existingApi = getRecaptchaApi();
        if (existingApi) {
            return Promise.resolve(existingApi);
        }

        if (!recaptchaSiteKey) {
            return Promise.resolve(null);
        }

        if (recaptchaLoadPromise) {
            return recaptchaLoadPromise;
        }

        recaptchaLoadPromise = new Promise(function (resolve) {
            const resolveApi = function () {
                resolve(getRecaptchaApi());
            };

            let script = document.querySelector('script[data-havu-recaptcha="true"]');
            if (!script) {
                script = document.createElement('script');
                script.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(recaptchaSiteKey);
                script.async = true;
                script.defer = true;
                script.dataset.havuRecaptcha = 'true';
                document.head.appendChild(script);
            }

            script.addEventListener('load', resolveApi, { once: true });
            script.addEventListener('error', function () { resolve(null); }, { once: true });

            window.setTimeout(resolveApi, 3500);
        });

        return recaptchaLoadPromise;
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

        ensureRecaptchaApi().then(function (recaptchaApi) {
            if (!recaptchaApi || !recaptchaSiteKey) {
                setLoading(false);
                showAlert(i18n.genericError, true);
                return;
            }

            recaptchaApi.ready(function () {
                recaptchaApi.execute(recaptchaSiteKey, { action: 'message' })
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
                    })
                    .catch(function () {
                        setLoading(false);
                        showAlert(i18n.genericError, true);
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
        if (!bootstrapApi || !bootstrapApi.Modal) {
            return;
        }
        bootstrapApi.Modal.getOrCreateInstance(modal).show();
    };
}());
