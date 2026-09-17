import ExpandableElement from '../../../scripts/helpers/ExpandableElement.js';

export default class SiteFooter {
    constructor(element) {
        this.el = element;
        this.isMobile = window.matchMedia('(max-width: 768px)').matches;

        if (!this.isMobile) {
            return;
        }

        // The five link columns only.
        //
        // Scoped to __menus rather than the whole footer, because the legal
        // strip below is also a .menu-list and has no heading to expand it
        // from: collapsed by this, it would simply be gone on a phone. It also
        // has no id, so ExpandableElement would bail on it and log an error on
        // every mobile page load of every page on the site.
        this.menuEls = this.el.querySelectorAll('.site-footer__menus .menu-list');
        this.expandableEls = {};

        this.init();
    }

    init() {
        // ---------------------------------------------------------------------
        // Set up sub menu expanding/collapsing functionality using ExpandableElement.
        // ---------------------------------------------------------------------
        if (this.menuEls.length > 0) {
            this.menuEls.forEach((element, index) => {
                if (element instanceof Element) {
                    const expandableEl = new ExpandableElement(element);
                    this.expandableEls[element.id] = expandableEl;


                    if (index === 0) {
                        expandableEl.expand();
                    } else {
                        expandableEl.collapse();
                    }
                }
            });
        }
    }
}
