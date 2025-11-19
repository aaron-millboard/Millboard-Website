import AnchorLinks from './AnchorLinks.js';

window.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.anchor-links');

    [...items].forEach((item) => {
        new AnchorLinks(item);
    });
});
