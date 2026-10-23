/**
 * Assets -- live search and paging.
 *
 * Progressive enhancement over a panel that already works. Without this script
 * the search is a GET form and paging is a "Show more" link carrying the
 * cursor in `mbcx`; both still work. This replaces the round trip with a fetch
 * and the link with a numbered pager.
 *
 * ⚠️ The cursor from the endpoint is OPAQUE. It is not an item count, and the
 * two branches of Client::contents() do not even measure it in the same units
 * (a raw Canto offset for staff, a position in the partner index otherwise).
 * So pages are remembered rather than calculated: `cursors[n]` is the cursor
 * that produced page n, learnt only by having been there. That is also why
 * the pager lists just the pages the viewer has reached plus the next one, and
 * can never say "of 12" -- there is no total to count towards, deliberately,
 * because the real total includes assets this person may not see.
 */

const PANEL = '[data-mb-assets]';
const DEBOUNCE_MS = 300;

/** Strings and configuration come from PHP, so they reach Loco. */
function config(panel) {
    return {
        endpoint: panel.dataset.endpoint || '',
        action: panel.dataset.action || '',
        nonce: panel.dataset.nonce || '',
        node: panel.dataset.node || '',
        scheme: panel.dataset.scheme || '',
        searching: panel.dataset.searching || 'Searching…',
        countTemplate: panel.dataset.countTemplate || '',
        nextLabel: panel.dataset.nextLabel || 'Next',
        failed: panel.dataset.failed || '',
    };
}

/**
 * Keep the grid's shape while a page loads.
 *
 * Emptying it first would collapse the panel and throw the rest of the page
 * up the screen, which on a slow reply reads as the content having vanished.
 */
function showSkeleton(grid, count) {
    grid.setAttribute('aria-busy', 'true');
    grid.innerHTML = '';

    for (let i = 0; i < count; i++) {
        const li = document.createElement('li');

        li.className = 'mb-assets__item mb-assets__item--skeleton';
        li.setAttribute('aria-hidden', 'true');
        grid.appendChild(li);
    }
}

function init(panel) {
    const grid = panel.querySelector('[data-mb-assets-grid]');
    const countEl = panel.querySelector('[data-mb-assets-count]');
    const emptyEl = panel.querySelector('[data-mb-assets-empty]');
    const input = panel.querySelector('input[name="mbcq"]');
    const form = panel.querySelector('form');

    if (!grid || !input) {
        return;
    }

    const cfg = config(panel);

    if (!cfg.endpoint || !cfg.nonce) {
        return;
    }

    // A polite region, announced only when it changes. assertive would
    // interrupt someone mid-word on every keystroke.
    const status = document.createElement('p');

    status.className = 'mb-assets__status';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    grid.parentNode.insertBefore(status, grid);

    const pager = document.createElement('nav');

    pager.className = 'mb-assets__pager';
    pager.hidden = true;
    grid.parentNode.insertBefore(pager, grid.nextSibling);

    // The server-rendered "Show more" link is the no-JS path. It has been
    // replaced, so it goes rather than sitting under a pager that does the
    // same job.
    const legacyMore = panel.querySelector('.mb-assets__more');

    if (legacyMore) {
        legacyMore.remove();
    }

    // cursors[n] produced page n. Page 0 always starts at 0.
    let cursors = [0];
    let page = 0;
    let more = panel.dataset.more === '1';
    let query = panel.dataset.query || '';
    let inFlight = null;

    if (panel.dataset.next) {
        cursors[1] = parseInt(panel.dataset.next, 10) || 0;
    }

    function renderPager() {
        pager.innerHTML = '';

        const reached = cursors.length - (more ? 0 : 1);
        const highest = Math.max(reached, page + 1);

        if (highest <= 1) {
            pager.hidden = true;
            return;
        }

        pager.hidden = false;

        for (let n = 0; n < highest; n++) {
            const button = document.createElement('button');

            button.type = 'button';
            button.className = 'mb-assets__page';
            button.textContent = String(n + 1);

            if (n === page) {
                button.setAttribute('aria-current', 'page');
                button.classList.add('is-current');
            }

            // A page nobody has reached yet has no cursor, so it cannot be
            // jumped to. Only the one immediately after the last is offered.
            button.disabled = cursors[n] === undefined;
            button.addEventListener('click', () => load(n));
            pager.appendChild(button);
        }

        const next = document.createElement('button');

        next.type = 'button';
        next.className = 'mb-assets__page mb-assets__page--next';
        next.textContent = cfg.nextLabel;
        next.disabled = !more;
        next.addEventListener('click', () => load(page + 1));
        pager.appendChild(next);
    }

    // What the server put there: a folder's name, or nothing at the top level.
    const originalCount = countEl ? countEl.textContent : '';

    function describe(shown) {
        if (!countEl) {
            return;
        }

        // Browsing rather than searching: the folder's own name is the right
        // label, and a page count would say nothing a folder name does not.
        if (!query || !cfg.countTemplate) {
            countEl.textContent = originalCount;

            return;
        }

        // Describes the page on screen, never a total. There is no total to
        // give: the real one counts assets this person may not see.
        countEl.textContent = cfg.countTemplate
            .replace('%1$d', String(page + 1))
            .replace('%2$d', String(shown))
            .replace('%3$s', query);
    }

    async function load(target) {
        const start = cursors[target];

        if (start === undefined) {
            return;
        }

        // Only the newest reply may paint. Typing "oak" fires three requests
        // and they can land out of order, so without this the grid can settle
        // on the results for "oa".
        const token = {};

        inFlight = token;

        status.textContent = cfg.searching;
        showSkeleton(grid, Math.min(grid.children.length || 12, 24));

        const body = new URLSearchParams({
            action: cfg.action,
            nonce: cfg.nonce,
            q: query,
            node: cfg.node,
            scheme: cfg.scheme,
            start: String(start),
        });

        let payload = null;

        try {
            const response = await fetch(cfg.endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body,
            });

            payload = await response.json();
        } catch (e) {
            payload = null;
        }

        if (inFlight !== token) {
            return;
        }

        grid.removeAttribute('aria-busy');

        if (!payload || !payload.success) {
            const message = (payload && payload.data && payload.data.message) || cfg.failed;

            grid.innerHTML = '';
            status.textContent = message;
            pager.hidden = true;

            return;
        }

        const data = payload.data;

        page = target;
        more = Boolean(data.more);
        cursors = cursors.slice(0, page + 1);
        cursors[page + 1] = data.next_start;

        grid.innerHTML = data.html;
        status.textContent = '';

        if (emptyEl) {
            emptyEl.hidden = data.count > 0;
        }

        describe(data.count);
        renderPager();

        // Back to the top of the results, not the top of the document: the
        // search box stays in view so another word can be tried immediately.
        if (target !== 0 || query) {
            grid.scrollIntoView({ block: 'nearest' });
        }
    }

    let timer = null;

    input.addEventListener('input', () => {
        window.clearTimeout(timer);

        timer = window.setTimeout(() => {
            const next = input.value.trim();

            if (next === query) {
                return;
            }

            query = next;
            cursors = [0];
            load(0);
        }, DEBOUNCE_MS);
    });

    // The form still submits without JS. With it, a press of Return should
    // search in place rather than reload the panel.
    if (form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            window.clearTimeout(timer);
            query = input.value.trim();
            cursors = [0];
            load(0);
        });
    }

    renderPager();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(PANEL).forEach(init);
});
