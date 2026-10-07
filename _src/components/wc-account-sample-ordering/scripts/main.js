/**
 * Sample ordering -- show "Clear order" only once there is an order.
 *
 * The widget keeps its quantities in a JavaScript object of its own and
 * publishes the running totals into the basket bar. Rather than reach into
 * that object, or edit the widget's file, this watches the line counter it
 * already writes and toggles a class on the container. The stylesheet does the
 * rest.
 *
 * Watching the counter rather than binding to the steppers matters: the
 * accordion bodies are re-rendered on every search, which destroys and
 * recreates every stepper, so any listener bound to them would be lost. The
 * counter element survives.
 */

const ROOT = '.mb-sof';
const COUNTER = '#bar-lines';
const READY_CLASS = 'has-order';

function sync(root, counter) {
    const lines = parseInt(counter.textContent, 10);

    root.classList.toggle(READY_CLASS, Number.isFinite(lines) && lines > 0);
}

function init(root) {
    const counter = root.querySelector(COUNTER);

    if (!counter) {
        return;
    }

    sync(root, counter);

    // characterData with subtree: the widget replaces the text node inside the
    // <strong>, which childList alone on the element would not always report.
    const observer = new MutationObserver(() => sync(root, counter));

    observer.observe(counter, {
        childList: true,
        characterData: true,
        subtree: true,
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(ROOT).forEach(init);
});
