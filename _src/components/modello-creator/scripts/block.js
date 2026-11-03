import ModelloCreator from './ModelloCreator.js';

window.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('[data-modello-creator]');

    [...items].forEach((item) => {
        new ModelloCreator(item);
    });
});
