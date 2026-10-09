// Summit pre-arrival form.
//
// Collects the delegate's answers and posts them to HubSpot's form submission
// endpoint. The portal and form GUID come from data attributes on the form, so
// pointing the page at a different form needs no code change. Field names are
// HubSpot internal property names and must exist on the target form, because
// HubSpot drops any submitted field the form does not define.

const form = document.querySelector('[data-review-form]');

if (form) {
    const $ = (sel) => form.querySelector(sel);
    const val = (name) => (form.elements[name] ? form.elements[name].value.trim() : '');
    const checked = (name) => !!(form.elements[name] && form.elements[name].checked);
    const radio = (name) => {
        const el = form.querySelector('input[name="' + name + '"]:checked');
        return el ? el.value : '';
    };

    const submit = $('[data-review-submit]');
    const hint = $('[data-review-hint]');
    const errorBox = $('[data-review-error]');
    const chips = Array.from(form.querySelectorAll('[data-chip]'));
    const selectedChips = () => chips.filter((c) => c.getAttribute('aria-pressed') === 'true').map((c) => c.dataset.chip);

    const state = () => {
        const diet = radio('diet');
        const access = radio('access');
        const needs = diet === 'yes' || access === 'yes' ? 'yes' : '';
        const dietOk = diet === 'no' || (diet === 'yes' && (selectedChips().length > 0 || val('summit_dietary_notes') !== ''));
        const accessOk = access === 'no' || (access === 'yes' && val('summit_accessibility_requirements') !== '');
        const consentOk = !needs || checked('summit_health_data_consent');
        const done = {
            nda: checked('summit_nda_accepted'),
            hs: checked('summit_hs_accepted'),
            needs: dietOk && accessOk && consentOk,
            photo: radio('summit_photo_consent') !== '',
            comp: checked('summit_competition_law_accepted'),
            data: checked('summit_data_consent'),
        };
        const int = form.dataset.audience === 'INT';
        const detailKeys = ['firstname', 'lastname', 'company'].concat(int ? ['summit_country', 'summit_mobile'] : []);
        const details = detailKeys.every((k) => val(k) !== '') && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val('email'));
        // INT only: either flights are given, or the delegate says they are not booked yet.
        const notBooked = checked('summit_flights_not_booked');
        const travel = !int || notBooked || FLIGHT_REQUIRED.every((k) => val(k) !== '');
        return { diet, access, needs, done, details, int, notBooked, travel };
    };

    const FLIGHT_REQUIRED = ['summit_arrival_airline', 'summit_arrival_flight', 'summit_arrival_airport', 'summit_arrival_date', 'summit_arrival_time'];
    const diaryDepts = () => Array.from(form.querySelectorAll('input[name="diary_dept"]:checked')).map((i) => i.value);
    const diaryNote = (dept) => {
        const ta = form.querySelector('[data-diary-note="' + dept + '"] textarea');
        return ta ? ta.value.trim() : '';
    };

    const labels = { nda: '01', hs: '02', needs: '03', photo: '04', comp: '05', data: '06' };

    const render = () => {
        const s = state();
        const keys = Object.keys(s.done);
        const count = keys.filter((k) => s.done[k]).length;

        const show = { 'diet=yes': s.diet === 'yes', 'access=yes': s.access === 'yes', 'needs=yes': !!s.needs };
        form.querySelectorAll('[data-show-when]').forEach((el) => {
            el.hidden = !show[el.dataset.showWhen];
        });
        keys.forEach((k) => {
            const badge = form.querySelector('[data-done="' + k + '"]');
            if (badge) badge.hidden = !s.done[k];
        });

        if (s.int) {
            const ticked = diaryDepts();
            form.querySelector('[data-flights]').hidden = s.notBooked;
            form.querySelector('[data-diary-notes]').hidden = ticked.length === 0;
            form.querySelectorAll('[data-diary-note]').forEach((el) => {
                el.hidden = !ticked.includes(el.dataset.diaryNote);
            });
            form.querySelector('[data-done="travel"]').hidden = !s.travel;
        }

        $('[data-review-count]').textContent = String(count);
        $('[data-review-bar]').style.width = (count / 6) * 100 + '%';

        const todo = [!s.details && 'your details', !s.travel && 'your travel'].concat(keys.filter((k) => !s.done[k]).map((k) => labels[k])).filter(Boolean);
        submit.disabled = todo.length > 0;
        hint.textContent = todo.length ? 'Still to complete: ' + todo.join(', ') + '.' : 'Ready to submit.';
        return s;
    };

    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            chip.setAttribute('aria-pressed', chip.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
            render();
        });
    });

    // Choosing "No requirements" clears anything entered under "Yes".
    form.querySelectorAll('input[name="diet"]').forEach((r) => {
        r.addEventListener('change', () => {
            if (r.value === 'no') {
                chips.forEach((c) => c.setAttribute('aria-pressed', 'false'));
                form.elements.summit_dietary_notes.value = '';
            }
        });
    });

    form.addEventListener('input', render);
    form.addEventListener('change', render);

    const fields = (s) => {
        const f = {
            firstname: val('firstname'),
            lastname: val('lastname'),
            company: val('company'),
            email: val('email'),
            summit_nda_accepted: 'true',
            summit_hs_accepted: 'true',
            summit_dietary_requirements: s.diet === 'no' ? 'No requirements' : selectedChips().join(';'),
            summit_dietary_notes: s.diet === 'yes' ? val('summit_dietary_notes') : '',
            summit_accessibility_requirements: s.access === 'no' ? 'No requirements' : val('summit_accessibility_requirements'),
            summit_photo_consent: radio('summit_photo_consent'),
            summit_competition_law_accepted: 'true',
            summit_data_consent: 'true',
        };
        if (s.needs) f.summit_health_data_consent = 'true';
        if (s.int) {
            f.summit_country = val('summit_country');
            f.summit_mobile = val('summit_mobile');
            if (s.notBooked) {
                f.summit_flights_not_booked = 'true';
            } else {
                FLIGHT_REQUIRED.concat(['summit_arrival_from']).forEach((k) => { f[k] = val(k); });
            }
            const depts = diaryDepts();
            f.summit_open_diary_departments = depts.join(';');
            f.summit_open_diary_topics = depts.filter((d) => diaryNote(d) !== '').map((d) => d + ': ' + diaryNote(d)).join('\n');
        }
        return Object.keys(f)
            .filter((k) => f[k] !== '')
            .map((k) => ({ objectTypeId: '0-1', name: k, value: f[k] }));
    };

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const s = render();
        if (submit.disabled) return;

        errorBox.hidden = true;
        submit.disabled = true;
        const original = submit.textContent;
        submit.textContent = 'Sending…';

        const payload = fields(s);
        const pageUri = window.location.href;

        // Primary route: our own endpoint, which records the submission before
        // forwarding it to HubSpot, so nothing is lost if HubSpot is down.
        // Fallback: if our server cannot be reached or cannot log, post to
        // HubSpot directly so the delegate is not turned away.
        const viaSite = () => fetch(form.dataset.endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ form: form.dataset.form, pageUri, fields: payload.map((f) => ({ name: f.name, value: f.value })) }),
        });
        const direct = () => fetch('https://api.hsforms.com/submissions/v3/integration/submit/'
            + encodeURIComponent(form.dataset.portal) + '/' + encodeURIComponent(form.dataset.form), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fields: payload, context: { pageUri, pageName: document.title } }),
        });

        viaSite()
            .then((r) => (r.status >= 500 || r.status === 404 || r.status === 403 ? direct() : r))
            .catch(() => direct())
            .then((r) => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const thanks = document.querySelector('[data-review-thanks]');
                thanks.querySelector('[data-review-thanks-title]').textContent = 'Thank you, ' + val('firstname');
                form.hidden = true;
                thanks.hidden = false;
                thanks.focus();
                thanks.scrollIntoView({ behavior: 'smooth', block: 'start' });
            })
            .catch(() => {
                // Nothing is lost: the answers are still on screen.
                errorBox.textContent = 'Sorry, we could not send that. Please try again, or email enquiries@millboard.com.';
                errorBox.hidden = false;
                submit.textContent = original;
                render();
            });
    });

    render();
}
