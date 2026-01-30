import LanguageSwitcher from './LanguageSwitcher.js';

window.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.language-switcher');

    [...items].forEach((item) => {
        new LanguageSwitcher(item);
    });
});
