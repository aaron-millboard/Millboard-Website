import Gallery from '../../gallery/scripts/Gallery.js';

window.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.gallery-loop[data-lightbox]');

    [...items].forEach((item) => {
        new Gallery(item);
    });
});
