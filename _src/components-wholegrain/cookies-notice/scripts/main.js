import CookiesNotice from './CookiesNotice.js';

window.addEventListener('DOMContentLoaded', () => {
    const element = document.querySelector('.cookies-notice');

    if (element instanceof Element) {
        new CookiesNotice(element);
    }
});
