/* eslint-disable-next-line import/no-unresolved */
import './components*/**/scripts/main.js';
import ScrollWatcher from './scripts/helpers/ScrollWatcher.js';
import initPartnerPhoneReveal from './scripts/PartnerPhoneReveal.js';
import initOpeningStatus from './scripts/OpeningStatus.js';

document.addEventListener('DOMContentLoaded', () => {
    new ScrollWatcher();

    // Partner phone buttons appear on profiles, the finder cards and the enquiry form,
    // so the behaviour is bound once here rather than repeated in each component.
    initPartnerPhoneReveal();

    // Open-or-closed lines, likewise on both the finder cards and the profiles. These
    // have to be worked out here rather than in PHP because the pages they sit on are
    // served from the full page cache; see OpeningStatus.js.
    initOpeningStatus();
});
