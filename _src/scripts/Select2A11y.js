/**
 * Give select2's custom comboboxes an accessible name (WCAG 4.1.2).
 *
 * select2 (used by WooCommerce's country/state fields and the theme's custom
 * checkout selects) replaces a labelled native <select> with its own markup:
 * `.select2-selection[role=combobox]` wrapping `.select2-selection__rendered`
 * [role=textbox]. Neither inherits the native select's <label>, so a screen
 * reader announces an unnamed control. We copy the native select's accessible
 * name onto the select2 widget.
 *
 * The container id is `select2-<nativeSelectId>-container`, so the native select
 * (and its `label[for]`) is recoverable. A couple of delayed passes catch
 * select2 initialising after DOMContentLoaded and re-rendering a dependent field
 * (e.g. state after country).
 */
export default function initSelect2A11y() {
    const nameFor = (selectId) => {
        const lbl = document.querySelector(`label[for="${CSS.escape(selectId)}"]`);
        if (lbl && lbl.textContent.trim()) return lbl.textContent.trim();
        const native = document.getElementById(selectId);
        return native ? (native.getAttribute('aria-label') || '').trim() : '';
    };

    const scan = () => {
        document.querySelectorAll('.select2-selection__rendered[id^="select2-"]').forEach((rendered) => {
            const match = rendered.id.match(/^select2-(.+)-container$/);
            if (!match) return;
            const name = nameFor(match[1]);
            if (!name) return;
            if (!rendered.getAttribute('aria-label')) {
                rendered.setAttribute('aria-label', name);
            }
            const combo = rendered.closest('.select2-selection');
            if (combo && !combo.getAttribute('aria-label') && !combo.getAttribute('aria-labelledby')) {
                combo.setAttribute('aria-label', name);
            }
        });
    };

    if (!document.querySelector('.select2-container, select.wc-enhanced-select, select[data-select2-id], form.checkout')) {
        return; // no select2 context on this page
    }
    scan();
    [500, 1500, 3000].forEach((t) => window.setTimeout(scan, t));
}
