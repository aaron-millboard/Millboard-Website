// Summit colleague feedback ("What did you hear?").
//
// Collects the answers and posts them to our own endpoint, which stores them in WordPress
// (Summit > Colleague feedback). Nothing goes to HubSpot. The server checks every answer again
// against the same lists the markup was drawn from, so this script only decides what is showing,
// when the form is ready and what to send.
//
// Attendee notes: up to five cards are in the markup (a0_ ... a4_ field names). A card shows when
// the question before it was answered "Yes" to "Do you have notes on another attendee?".

const form = document.querySelector('[data-colleague-form]');

if (form) {
    const $ = (sel) => form.querySelector(sel);
    const val = (name) => (form.elements[name] ? form.elements[name].value.trim() : '');
    const radio = (name) => {
        const el = form.querySelector('input[name="' + name + '"]:checked');
        return el ? el.value : '';
    };
    const checks = (name) => Array.from(form.querySelectorAll('input[name="' + name + '"]:checked')).map((i) => i.value);

    let i18n = {};
    try { i18n = JSON.parse(form.dataset.i18n || '{}'); } catch (e) { i18n = {}; }
    const t = (key, fallback) => i18n[key] || fallback;

    const submit = $('[data-colleague-submit]');
    const hint = $('[data-colleague-hint]');
    const errorBox = $('[data-colleague-error]');
    const cards = Array.from(form.querySelectorAll('[data-attendee]'));

    // Which attendee cards are showing: the first once notes = Yes, then each one the previous card asks for.
    const visibleCards = () => {
        if (radio('notes') !== 'Yes') return [];
        const out = [];
        cards.forEach((card, i) => {
            if (i === 0 || (out.length === i && radio('a' + (i - 1) + '_more') === 'Yes')) out.push(i);
        });
        return out;
    };

    const render = () => {
        const opp = radio('opp');
        $('[data-opp-follow]').hidden = opp === '' || opp === 'No';

        const showing = visibleCards();
        $('[data-attendees]').hidden = showing.length === 0;
        cards.forEach((card, i) => {
            card.hidden = !showing.includes(i);
            const person = card.querySelector('[data-person]');
            if (person) person.hidden = !checks('a' + i + '_who').includes('Specific person');
        });

        const required = [
            ['Q1', val('name') !== ''],
            ['Q2', radio('team') !== ''],
            ['Q3', radio('scope') !== ''],
            ['Q4', checks('types').length > 0],
            ['Q5', radio('market') !== ''],
            ['Q6', radio('overall') !== ''],
            ['Q17', radio('notes') !== ''],
        ];
        showing.forEach((i) => {
            const tag = showing.length > 1 ? ' (attendee ' + (i + 1) + ')' : '';
            required.push(['Q18' + tag, val('a' + i + '_name') !== '']);
            required.push(['Q22' + tag, val('a' + i + '_note') !== '']);
        });

        const done = required.filter((r) => r[1]).length;
        const todo = required.filter((r) => !r[1]).map((r) => r[0]);

        $('[data-colleague-count]').textContent = String(done);
        $('[data-colleague-total]').textContent = String(required.length);
        $('[data-colleague-bar]').style.width = (done / required.length) * 100 + '%';
        submit.disabled = todo.length > 0;
        hint.textContent = todo.length ? t('todo', 'Still to answer: ') + todo.join(', ') + '.' : t('ready', 'Ready to submit.');
    };

    // "None specifically" stands alone: choosing it clears the products, and choosing a product clears it.
    form.querySelectorAll('input[name="prods"]').forEach((box) => {
        box.addEventListener('change', () => {
            if (!box.checked) return;
            form.querySelectorAll('input[name="prods"]').forEach((o) => {
                if (o !== box && (box.hasAttribute('data-none') || o.hasAttribute('data-none'))) o.checked = false;
            });
        });
    });

    form.addEventListener('input', render);
    form.addEventListener('change', render);

    const attendee = (i) => ({
        name: val('a' + i + '_name'),
        company: val('a' + i + '_company'),
        type: radio('a' + i + '_type'),
        topic: radio('a' + i + '_topic'),
        note: val('a' + i + '_note'),
        who: checks('a' + i + '_who'),
        person: val('a' + i + '_person'),
        urgency: radio('a' + i + '_urgency'),
        hubspot: radio('a' + i + '_hubspot'),
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        render();
        if (submit.disabled) return;

        errorBox.hidden = true;
        submit.disabled = true;
        const original = submit.textContent;
        submit.textContent = t('sending', 'Sending…');

        const answers = {
            name: val('name'),
            team: radio('team'),
            scope: radio('scope'),
            types: checks('types'),
            market: radio('market'),
            overall: radio('overall'),
            again: radio('again'),
            feeling: val('feeling'),
            best: val('best'),
            less: val('less'),
            prods: checks('prods'),
            concerns: val('concerns'),
            brand: radio('brand'),
            opp: radio('opp'),
            logged: radio('logged'),
            opp_notes: val('opp_notes'),
            notes: radio('notes'),
            attendees: visibleCards().map(attendee),
            future: val('future'),
            quotes: val('quotes'),
            permission: radio('permission'),
            extra: val('extra'),
        };

        fetch(form.dataset.endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pageUri: window.location.href, answers }),
        })
            .then((r) => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const thanks = document.querySelector('[data-colleague-thanks]');
                form.hidden = true;
                thanks.hidden = false;
                thanks.focus();
                thanks.scrollIntoView({ behavior: 'smooth', block: 'start' });
                submit.textContent = original;
            })
            .catch(() => {
                // Nothing is lost: the answers are still on screen.
                errorBox.textContent = t('error', 'Sorry, we could not send that. Please try again, or email enquiries@millboard.com.');
                errorBox.hidden = false;
                submit.textContent = original;
                render();
            });
    });

    // "Submit another response": a blank form, so notes from the last one do not carry over.
    const again = document.querySelector('[data-colleague-again]');
    if (again) {
        again.addEventListener('click', () => {
            form.reset();
            errorBox.hidden = true;
            document.querySelector('[data-colleague-thanks]').hidden = true;
            form.hidden = false;
            render();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    render();
}
