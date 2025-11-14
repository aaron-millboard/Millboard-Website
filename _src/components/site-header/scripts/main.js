import SiteHeader from './SiteHeader.js';

window.addEventListener('DOMContentLoaded', () => {
    const element = document.querySelector('.site-header');

    new SiteHeader(element);
});
