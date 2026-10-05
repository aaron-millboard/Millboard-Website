import ProductVisualiser from './ProductVisualiser.js';

window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.product-visualiser').forEach((el) => new ProductVisualiser(el));
});
