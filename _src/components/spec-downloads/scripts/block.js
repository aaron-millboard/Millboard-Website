/**
 * Spec downloads: filter the rows by document type.
 *
 * The tab group is rendered hidden and shown here, so without JavaScript every
 * document is listed and nothing is hidden.
 */
const init = () => {
    document.querySelectorAll('.spec-downloads').forEach((block) => {
        const tabs = block.querySelector('.spec-downloads__tabs');

        if (!tabs) {
            return;
        }

        const buttons = [...tabs.querySelectorAll('button[data-filter]')];
        const rows = [...block.querySelectorAll('.spec-downloads__file')];
        const status = block.querySelector('[data-filter-status]');

        tabs.hidden = false;

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                const filter = button.dataset.filter;

                buttons.forEach((other) => {
                    const active = other === button;

                    other.setAttribute('aria-pressed', active ? 'true' : 'false');
                    other.classList.toggle('g-button--solid', active);
                });

                rows.forEach((row) => {
                    row.hidden = filter !== 'all' && row.dataset.type !== filter;
                });

                if (status) {
                    const shown = rows.filter((row) => !row.hidden).length;

                    status.textContent = `${button.textContent.trim()}: showing ${shown} of ${rows.length} documents`;
                }
            });
        });
    });
};

// The script can run after DOMContentLoaded has fired (a delayed-JS plugin), so do not wait for an event that has gone.
if (document.readyState === 'loading') {
    window.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
