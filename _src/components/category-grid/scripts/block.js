import CategoryGrid from './CategoryGrid.js';

window.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.category-grid');

    [...items].forEach((item) => {
        new CategoryGrid(item);
    });
});
