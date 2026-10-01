import AdviceCategorySlider from './AdviceCategorySlider.js';

window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.advice-category-slider').forEach((element) => {
        new AdviceCategorySlider(element);
    });
});
