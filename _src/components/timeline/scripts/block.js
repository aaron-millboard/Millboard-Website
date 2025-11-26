import Timeline from './Timeline.js';

window.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.timeline');

    [...items].forEach((item) => {
        new Timeline(item);
    });
});
