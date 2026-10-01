import AdviceFilterBar from './AdviceFilterBar.js';

window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-advice-filter]').forEach((element) => {
        new AdviceFilterBar(element);
    });
});
