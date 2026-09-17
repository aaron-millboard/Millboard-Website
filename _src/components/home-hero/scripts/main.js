import HomeHero from './HomeHero.js';

window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.home-hero').forEach((element) => {
        new HomeHero(element);
    });
});
