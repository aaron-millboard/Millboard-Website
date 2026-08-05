import SampleBasket from './SampleBasket.js';

window.addEventListener('DOMContentLoaded', () => {
    if (typeof window.granolaProductSamples === 'undefined') return;

    new SampleBasket(window.granolaProductSamples);
});
