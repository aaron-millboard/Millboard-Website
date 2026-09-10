// Summit registration form.
//
// The two rules (must be on the invite list, two people per company) are
// enforced entirely by the REST gate in Theme/Summit/Gate.php. Nothing here
// decides anything: the invite list is never sent to the browser, because it is
// 152 partner names and email addresses. This script only collects the fields,
// asks the server, and renders whatever answer comes back.
//
// The email check on blur is a courtesy so a visitor who was not invited finds
// out before filling the rest in. It is advisory; the submit re-runs every check.

const forms = document.querySelectorAll('[data-summit-form]');

if (forms.length) {
    forms.forEach(function (form) {
        const notice = form.querySelector('[data-summit-notice]');
        const submit = form.querySelector('[data-summit-submit]');
        const submitLabel = form.querySelector('[data-summit-submit-label]');
        const emailField = form.querySelector('[name="email"]');
        const success = form.parentElement
            ? form.parentElement.querySelector('[data-summit-success]')
            : null;

        const originalLabel = submitLabel ? submitLabel.textContent : '';

        // Which audience this page serves. The gate checks the submitted value
        // against the invite's own audience, so a US invitee cannot register
        // through the UK page and be given one day instead of three.
        const audience = form.getAttribute('data-summit-audience') || '';

        let blocked = false;

        function showNotice(message) {
            if (!notice) {
                return;
            }
            notice.textContent = message;
            notice.hidden = !message;
        }

        function clearFieldErrors() {
            form.querySelectorAll('[data-summit-error]').forEach(function (el) {
                el.textContent = '';
                el.hidden = true;
            });
            form.querySelectorAll('.summit-form__input--invalid').forEach(function (el) {
                el.classList.remove('summit-form__input--invalid');
            });
        }

        function showFieldErrors(errors) {
            Object.keys(errors || {}).forEach(function (name) {
                const target = form.querySelector('[data-summit-error="' + name + '"]');
                if (target) {
                    target.textContent = errors[name];
                    target.hidden = false;
                }
                const input = form.querySelector('[name="' + name + '"], [name="' + name + '[]"]');
                if (input) {
                    input.classList.add('summit-form__input--invalid');
                }
            });

            // Move focus to the first thing that needs fixing, otherwise a
            // keyboard or screen-reader user has no idea anything changed.
            const first = form.querySelector('.summit-form__input--invalid');
            if (first && typeof first.focus === 'function') {
                first.focus();
            }
        }

        function setBusy(isBusy) {
            if (submit) {
                submit.disabled = isBusy || blocked;
            }
            if (submitLabel) {
                submitLabel.textContent = isBusy ? 'Please wait…' : originalLabel;
            }
        }

        function post(endpoint, body) {
            return fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.params && window.params.summit_nonce
                        ? window.params.summit_nonce
                        : ''
                },
                body: JSON.stringify(body)
            }).then(function (response) {
                return response.json().catch(function () {
                    return { ok: false, message: '' };
                });
            });
        }

        // --- advisory pre-check on the email field
        if (emailField && window.params && window.params.summit_check_endpoint) {
            emailField.addEventListener('blur', function () {
                const email = emailField.value.trim();

                // Nothing useful to ask about a half-typed address.
                if (!email || email.indexOf('@') === -1) {
                    return;
                }

                post(window.params.summit_check_endpoint, { email: email, audience: audience })
                    .then(function (data) {
                        if (data && data.ok) {
                            blocked = false;
                            showNotice('');
                        } else if (data && data.reason === 'invalid_email') {
                            // Let the normal field validation handle this one.
                            blocked = false;
                            showNotice('');
                        } else {
                            blocked = true;
                            showNotice((data && data.message) || '');
                        }
                        setBusy(false);
                    })
                    .catch(function () {
                        // A failed pre-check must never stop a genuine invitee
                        // submitting. The gate will decide on submit.
                        blocked = false;
                        showNotice('');
                        setBusy(false);
                    });
            });

            emailField.addEventListener('input', function () {
                if (blocked) {
                    blocked = false;
                    showNotice('');
                    setBusy(false);
                }
            });
        }

        // --- submission
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!window.params || !window.params.summit_register_endpoint) {
                showNotice(
                    'Registration is unavailable right now. Please reply to your invitation and we will register you.'
                );
                return;
            }

            clearFieldErrors();
            showNotice('');
            setBusy(true);

            const workshops = [];
            form.querySelectorAll('[name="workshops[]"]:checked').forEach(function (box) {
                workshops.push(box.value);
            });

            // A radio group returns nothing when untouched, which the gate reads
            // as a validation error rather than a silent default.
            function radio(name) {
                const checked = form.querySelector('[name="' + name + '"]:checked');
                return checked ? checked.value : '';
            }

            function field(name) {
                const el = form.querySelector('[name="' + name + '"]');
                return el ? el.value : '';
            }

            const optOut = form.querySelector('[name="opt_out_marketing"]');

            // Only the fields this audience actually renders are sent. The
            // others are absent rather than empty, so the gate's per-audience
            // validation is not tripped by a question that was never asked.
            const payload = {
                audience: audience,
                first_name: field('first_name'),
                last_name: field('last_name'),
                email: field('email'),
                company_typed: field('company_typed'),
                preferred_date: field('preferred_date'),
                factory_tour: radio('factory_tour'),
                attending: radio('attending'),
                email_contact: radio('email_contact'),
                phone_contact: radio('phone_contact'),
                workshops: workshops,
                opt_out_marketing: optOut ? optOut.checked : false,
                company_website: field('company_website')
            };

            post(window.params.summit_register_endpoint, payload)
                .then(function (data) {
                    setBusy(false);

                    if (data && data.ok) {
                        // Replace the form so the same person cannot register twice
                        // by pressing the button again.
                        form.hidden = true;
                        if (success) {
                            // An FR guest who said they cannot come gets the
                            // server's own wording, not "your place is
                            // registered", which would be plainly wrong.
                            if (data.reason === 'declined' && data.message) {
                                const heading = success.querySelector('[data-summit-success-heading]');
                                const text = success.querySelector('[data-summit-success-text]');
                                if (heading) {
                                    heading.textContent = data.message;
                                }
                                if (text) {
                                    text.hidden = true;
                                }
                            }
                            success.hidden = false;
                            if (typeof success.focus === 'function') {
                                success.setAttribute('tabindex', '-1');
                                success.focus();
                            }
                        } else {
                            showNotice(data.message || 'Thank you, your place is registered.');
                        }
                        return;
                    }

                    if (data && data.errors) {
                        showFieldErrors(data.errors);
                    }

                    if (data && data.message) {
                        showNotice(data.message);
                        if (notice && typeof notice.scrollIntoView === 'function') {
                            notice.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                        }
                    }
                })
                .catch(function () {
                    setBusy(false);
                    showNotice(
                        'Something went wrong sending your registration. Please try again, or reply to your invitation.'
                    );
                });
        });
    });
}
