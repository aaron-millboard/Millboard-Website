import ExpandableElement from '../../../../scripts/helpers/ExpandableElement.js';

/**
 * Language switcher.
 *
 * Handles the language switcher functionality.
 */

export default class LanguageSwitcher {
    constructor(element) {
        this.el = element;
        this.items = this.el.querySelector('.language-switcher__items');

        this.init();
    }

    init() {
        new ExpandableElement(this.items, {
            collapseOnFocusout: true,
        });
    }
}
