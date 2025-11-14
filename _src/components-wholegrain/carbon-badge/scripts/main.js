import CarbonBadge from './CarbonBadge.js';

document.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.carbon-badge');

    [...items].forEach((item) => {
        new CarbonBadge(item);
    });
});
