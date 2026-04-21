(function () {
    'use strict';

    const modal   = document.getElementById('feedbackModal');
    const form    = document.getElementById('feedback-form');
    const alertEl = document.getElementById('feedback-alert');
    const spinner = document.getElementById('feedback-submit-spinner');
    const btnText = document.getElementById('feedback-submit-text');
    const submitBtn = document.getElementById('feedback-submit');

    if (!form || !modal) return;

    const endpoint = modal.dataset.action;

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
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        setLoading(true);

        grecaptcha.ready(function () {
            grecaptcha.execute(RECAPTCHA_SITE_KEY, { action: 'feedback' })
                .then(function (token) {
                    document.getElementById('recaptcha-token').value = token;

                    fetch(endpoint, { method: 'POST', body: new FormData(form) })
                        .then(function (res) { return res.json(); })
                        .then(function (json) {
                            setLoading(false);
                            if (json.ok) {
                                showAlert('Kiitos! Viestisi on lähetetty.', false);
                                form.reset();
                                form.classList.remove('was-validated');
                            } else {
                                showAlert(json.error || 'Jokin meni pieleen. Yritä uudelleen.', true);
                            }
                        })
                        .catch(function () {
                            setLoading(false);
                            showAlert('Verkkovirhe. Tarkista yhteytesi ja yritä uudelleen.', true);
                        });
                });
        });
    });

    // Exposed for the game page inline trigger
    window.openFeedbackModal = function () {
        bootstrap.Modal.getOrCreateInstance(modal).show();
    };
}());
