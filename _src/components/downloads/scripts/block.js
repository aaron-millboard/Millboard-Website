import Downloads from './Downloads.js';

window.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.downloads');

    [...items].forEach((item) => {
        new Downloads(item);
    });
});
