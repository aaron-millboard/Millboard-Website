/* eslint-disable-next-line import/no-unresolved */
import './components*/**/scripts/main.js';
import ScrollWatcher from './scripts/helpers/ScrollWatcher.js';
import initPartnerPhoneReveal from './scripts/PartnerPhoneReveal.js';

document.addEventListener('DOMContentLoaded', () => {
    new ScrollWatcher();

    // Partner phone buttons appear on profiles, the finder cards and the enquiry form,
    // so the behaviour is bound once here rather than repeated in each component.
    initPartnerPhoneReveal();
});
