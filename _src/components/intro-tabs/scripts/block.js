import IntroTabs from './IntroTabs.js';

window.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.intro-tabs');

    [...items].forEach((item) => {
        new IntroTabs(item);
    });
});
