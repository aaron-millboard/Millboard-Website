import CookiesPreferences from './CookiesPreferences.js';

window.addEventListener('DOMContentLoaded', () => {
    const elements = document.querySelectorAll('.cookies-preferences');

    elements.forEach((element) => {
        new CookiesPreferences(element);
    });
});
