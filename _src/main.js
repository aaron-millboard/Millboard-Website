/* eslint-disable-next-line import/no-unresolved */
import './components*/**/scripts/main.js';
import ScrollWatcher from './scripts/helpers/ScrollWatcher.js';

document.addEventListener('DOMContentLoaded', () => {
    new ScrollWatcher();
});
