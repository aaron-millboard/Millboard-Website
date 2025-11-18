import SiteFooter from './SiteFooter.js';

window.addEventListener('DOMContentLoaded', () => {
    const element = document.querySelector('.site-footer');

    new SiteFooter(element);
});
