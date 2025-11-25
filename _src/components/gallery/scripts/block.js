import Gallery from './Gallery.js';

window.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.gallery[data-lightbox]');

    [...items].forEach((item) => {
        new Gallery(item);
    });
});
