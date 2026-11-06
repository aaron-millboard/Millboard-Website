// Summit feedback survey.
//
// Collects the answers and posts them to our own endpoint, which stores them in
// WordPress (Summit > Feedback survey). Nothing goes to HubSpot. The server
// checks every answer again against the same lists the markup was drawn from, so
// this script only has to decide when the form is ready and what to send.
// Radio and checkbox names come from the markup (obj_*, prod_*), so adding a
// statement or a product in Theme\Summit\Feedback needs no change here.

const form = document.querySelector('[data-feedback-form]');

if (form) {
    const $ = (sel) => form.querySelector(sel);
    const val = (name) => (form.elements[name] ? form.elements[name].value.trim() : '');
    const radio = (name) => {
        const el = form.querySelector('input[name="' + name + '"]:checked');
        return el ? el.value : '';
    };
    const names = (prefix) => Array.from(new Set(
        Array.from(form.querySelectorAll('input[type="radio"][name^="' + prefix + '"]')).map((i) => i.name)
    ));
    const interest = () => Array.from(form.querySelectorAll('input[name="interest"]:checked')).map((i) => i.value);

    // Strings for this page's language, set by the template. English is the fallback.
    let i18n = {};
    try { i18n = JSON.parse(form.dataset.i18n || '{}'); } catch (e) { i18n = {}; }
    const t = (key, fallback) => i18n[key] || fallback;

    const submit = $('[data-feedback-submit]');
    const hint = $('[data-feedback-hint]');
    const errorBox = $('[data-feedback-error]');
    const other = $('[data-session-other]');
    const objNames = names('obj_');
    const prodNames = names('prod_');

    // Question 7 counts towards progress but is optional, as in the design.
    const REQUIRED = [0, 1, 2, 3, 4, 5, 7];
    const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    const render = () => {
        const session = val('session');
        other.hidden = session !== 'Other';

        const done = [
            radio('overall') !== '',
            objNames.every((n) => radio(n) !== ''),
            radio('relevance') !== '',
            prodNames.every((n) => radio(n) !== '') && interest().length > 0,
            session !== '' && (session !== 'Other' || val('session_other') !== ''),
            radio('again') !== '',
            val('improve') !== '',
            val('market') !== '' && val('role') !== '' && (val('email') === '' || EMAIL.test(val('email'))),
        ];
        const count = done.filter(Boolean).length;
        const todo = REQUIRED.filter((i) => !done[i]).map((i) => '0' + (i + 1));

        $('[data-feedback-count]').textContent = String(count);
        $('[data-feedback-bar]').style.width = (count / done.length) * 100 + '%';
        submit.disabled = todo.length > 0;
        hint.textContent = todo.length ? t('todo', 'Still to answer: ') + todo.join(', ') + '.' : t('ready', 'Ready to submit.');
    };

    // "None of these" stands alone: choosing it clears the products, and choosing a product clears it.
    form.querySelectorAll('input[name="interest"]').forEach((box) => {
        box.addEventListener('change', () => {
            if (!box.checked) return;
            form.querySelectorAll('input[name="interest"]').forEach((o) => {
                if (o !== box && (box.hasAttribute('data-none') || o.hasAttribute('data-none'))) o.checked = false;
            });
        });
    });

    form.addEventListener('input', render);
    form.addEventListener('change', render);

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        render();
        if (submit.disabled) return;

        errorBox.hidden = true;
        submit.disabled = true;
        const original = submit.textContent;
        submit.textContent = t('sending', 'Sending…');

        const answers = {
            overall: radio('overall'),
            obj: {},
            relevance: radio('relevance'),
            prod: {},
            interest: interest(),
            prod_notes: val('prod_notes'),
            session: val('session'),
            session_other: val('session_other'),
            again: radio('again'),
            improve: val('improve'),
            market: val('market'),
            role: val('role'),
            name: val('name'),
            email: val('email'),
        };
        objNames.forEach((n) => { answers.obj[n.replace('obj_', '')] = radio(n); });
        prodNames.forEach((n) => { answers.prod[n.replace('prod_', '')] = radio(n); });

        fetch(form.dataset.endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ audience: form.dataset.audience, pageUri: window.location.href, answers }),
        })
            .then((r) => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const thanks = document.querySelector('[data-feedback-thanks]');
                form.hidden = true;
                thanks.hidden = false;
                thanks.focus();
                thanks.scrollIntoView({ behavior: 'smooth', block: 'start' });
            })
            .catch(() => {
                // Nothing is lost: the answers are still on screen.
                errorBox.textContent = t('error', 'Sorry, we could not send that. Please try again, or email enquiries@millboard.com.');
                errorBox.hidden = false;
                submit.textContent = original;
                render();
            });
    });

    render();
}
