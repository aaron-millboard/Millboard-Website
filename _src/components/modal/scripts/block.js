import Modal from './Modal.js';

/**
 * Initializes any modals on the page on document load.
 */
window.addEventListener('load', () => {
    const modals = document.querySelectorAll('.modal') ?? [];
    modals.forEach((modal) => {
        new Modal(modal);
    });
});
