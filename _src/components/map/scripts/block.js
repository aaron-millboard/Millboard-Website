import Map from './Map.js';

window.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.map');

    [...items].forEach((item) => {
        const focusOnSelect = false;
        new Map(item);
    });
});
