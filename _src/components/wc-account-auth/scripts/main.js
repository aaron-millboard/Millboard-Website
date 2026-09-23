/**
 * Sign in / create account -- the tabs.
 *
 * The markup ships with both forms visible and the tab strip hidden, so a page
 * without JavaScript is a stacked pair of complete forms rather than a single
 * form with its other half locked away behind a button that does nothing.
 * This turns that into the tabbed panel the design draws.
 */

const READY_CLASS = 'mb-auth--tabbed';

function initAuth(root) {
    const strip = root.querySelector('[data-mb-auth-tabs]');
    const tabs = Array.from(root.querySelectorAll('[data-mb-auth-tab]'));
    const panels = Array.from(root.querySelectorAll('[data-mb-auth-panel]'));

    // Only one form on the page (registration switched off): nothing to tab
    // between, so the strip stays hidden and the form stays as it is.
    if (!strip || tabs.length < 2 || panels.length < 2) {
        return;
    }

    const select = (name, { focus = false } = {}) => {
        tabs.forEach((tab) => {
            const isCurrent = tab.dataset.mbAuthTab === name;
            tab.classList.toggle('is-current', isCurrent);
            tab.setAttribute('aria-selected', isCurrent ? 'true' : 'false');
            tab.setAttribute('tabindex', isCurrent ? '0' : '-1');
        });

        panels.forEach((panel) => {
            panel.hidden = panel.dataset.mbAuthPanel !== name;
        });

        if (focus) {
            const current = tabs.find((tab) => tab.dataset.mbAuthTab === name);

            if (current) {
                current.focus();
            }
        }
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => select(tab.dataset.mbAuthTab));
    });

    // Left/right moves between tabs, which is what a tablist is expected to do.
    strip.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
            return;
        }

        const index = tabs.findIndex((tab) => tab.classList.contains('is-current'));

        if (index === -1) {
            return;
        }

        const step = event.key === 'ArrowRight' ? 1 : -1;
        const next = tabs[(index + step + tabs.length) % tabs.length];

        event.preventDefault();
        select(next.dataset.mbAuthTab, { focus: true });
    });

    strip.hidden = false;
    root.classList.add(READY_CLASS);

    select(root.dataset.mbAuthInitial === 'register' ? 'register' : 'signin');
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-mb-auth]').forEach(initAuth);
});
