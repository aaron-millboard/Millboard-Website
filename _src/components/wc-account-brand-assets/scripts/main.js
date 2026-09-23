/**
 * Brand assets -- notice a frame that was refused, and offer the tab instead.
 *
 * Canto sends `X-Frame-Options: DENY`. A refused frame does not fire an error
 * event, so the only reliable signal is what the frame contains afterwards:
 *
 *   - refused    -> the browser leaves an empty same-origin document behind,
 *                   so `contentDocument` reads and its body has nothing in it
 *   - loaded     -> the document is cross-origin, so touching it throws
 *
 * Reading the throw as success is the important half. This is deliberately
 * one-way: it only ever hides a frame that is demonstrably empty, so the day
 * Canto allows millboard.com as a frame ancestor the check stops firing and
 * the designed layout appears with no code change.
 */

const SETTLE_MS = 2500;

function frameIsBlocked(frame) {
    try {
        const doc = frame.contentDocument;

        // No document at all: nothing has been painted, treat as blocked.
        if (!doc) {
            return true;
        }

        // Same-origin and empty means the browser refused the real document
        // and left its own blank one behind.
        return !doc.body || doc.body.childElementCount === 0;
    } catch (error) {
        // Cross-origin. The frame loaded Canto, which is what we want.
        return false;
    }
}

function revealFallback(embed) {
    const frame = embed.querySelector('[data-mb-embed-frame]');
    const fallback = embed.querySelector('[data-mb-embed-fallback]');

    if (!frame || !fallback) {
        return;
    }

    frame.hidden = true;
    fallback.hidden = false;
    embed.classList.add('mb-account-embed--blocked');
}

function watch(embed) {
    const frame = embed.querySelector('[data-mb-embed-frame]');

    if (!frame) {
        return;
    }

    const check = () => {
        if (frameIsBlocked(frame)) {
            revealFallback(embed);
        }
    };

    // Once on load, and once after things have settled: a frame can be refused
    // before the load event and can also take a moment to give up.
    frame.addEventListener('load', check);
    window.setTimeout(check, SETTLE_MS);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-mb-embed]').forEach(watch);
});
