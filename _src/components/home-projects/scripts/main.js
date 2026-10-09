import HomeProjects from './HomeProjects.js';

window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.home-projects').forEach((element) => {
        new HomeProjects(element);
    });
});
