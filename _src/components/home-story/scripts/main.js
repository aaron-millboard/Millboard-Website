import HomeStory from './HomeStory.js';

window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.home-story').forEach((element) => {
        new HomeStory(element);
    });
});
