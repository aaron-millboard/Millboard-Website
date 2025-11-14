import Accordion from './Accordion.js';

window.addEventListener('DOMContentLoaded', () => {
    const elements = document.querySelectorAll('.accordion');

    elements.forEach((element) => {
        new Accordion(element);
    });
});
