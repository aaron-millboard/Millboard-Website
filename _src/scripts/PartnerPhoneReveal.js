/**
 * Makes partner "Call us" buttons usable on a desktop.
 *
 * A tel: link does nothing on most desktops, so a button labelled "Call us" was a dead
 * end for anyone not on a phone. The numbers are deliberately not printed on the page,
 * because every call needs to be attributable, so the answer is to reveal the number on
 * click rather than to publish it.
 *
 * On a touch device the link still dials straight away, which is the better behaviour
 * there. Devices are told apart by pointer type rather than by sniffing the user agent,
 * so a laptop with a touchscreen behaves like a desktop, which is what its owner
 * expects.
 *
 * Opt in per link with data-reveal-phone. Without JavaScript the tel: link is untouched,
 * so nothing is lost.
 */

const REVEAL_SELECTOR = 'a[href^="tel:"][data-reveal-phone]';

/**
 * True when the visitor is driving a mouse or trackpad rather than a finger.
 */
function hasFinePointer() {
    return typeof window.matchMedia === 'function'
        && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
}

/**
 * The dialable number, read back off the href so the markup stays the single source of
 * truth and the displayed number can never drift from the one being dialled.
 */
function numberFromHref(link) {
    return decodeURIComponent((link.getAttribute('href') || '').replace(/^tel:/i, '')).trim();
}

/**
 * Partner name and type for the tracking payload.
 *
 * Finder cards carry both on the row. A profile page has one partner, so the page
 * heading and the body class identify it.
 */
function partnerContext(link) {
    const row = link.closest('[data-map-item-post-type]');

    if (row) {
        const title = row.querySelector('.map__listing__title');

        return {
            name: title ? title.textContent.trim() : '',
            type: row.dataset.mapItemPostType || '',
        };
    }

    const heading = document.querySelector('h1');
    const types = ['distributor', 'installer', 'showroom', 'experience_centre'];

    return {
        name: heading ? heading.textContent.trim() : '',
        type: types.find((type) => document.body.classList.contains(`single-${type}`)) || '',
    };
}

/**
 * Reuses the map's existing event and parameters, so these clicks land in GA4 through
 * the GTM tag and custom dimensions that are already published. A dedicated event would
 * read better in reports but would not be measured at all until GTM was updated.
 */
function track(link, action) {
    // Inside the finder, Map.js already tracks these clicks. Reveal still applies there,
    // but tracking must not, or every call would be counted twice.
    if (link.closest('.map')) {
        return;
    }

    const {name, type} = partnerContext(link);

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event: 'map_listing_click',
        map_action: action,
        map_listing_name: name,
        map_listing_type: type,
    });
}

function reveal(link) {
    const number = numberFromHref(link);

    if (!number) {
        return;
    }

    const label = link.querySelector('[data-reveal-phone-label]');

    if (label) {
        // A button with its own words, e.g. "Call us": swap them for the number.
        label.textContent = number;
    } else {
        // Icon-only buttons, as used on the finder cards, have no words to replace, so
        // the number is appended instead. Setting textContent on the link would delete
        // the icon.
        const appended = document.createElement('span');

        appended.className = 'partner-phone-number';
        appended.textContent = number;
        link.appendChild(appended);
    }

    link.setAttribute('data-phone-revealed', 'true');

    // The number is now the visible label, so an aria-label saying "Call us" would
    // override it for a screen reader.
    link.removeAttribute('aria-label');
}

export default function initPartnerPhoneReveal() {
    document.addEventListener('click', (event) => {
        const link = event.target.closest(REVEAL_SELECTOR);

        if (!link) {
            return;
        }

        // Touch, or a number already on show: let the link dial.
        if (!hasFinePointer() || link.getAttribute('data-phone-revealed') === 'true') {
            track(link, 'phone');

            return;
        }

        event.preventDefault();
        reveal(link);
        track(link, 'phone_reveal');
    });
}
