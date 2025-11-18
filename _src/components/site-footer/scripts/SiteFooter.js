import ExpandableElement from '../../../scripts/helpers/ExpandableElement.js';

export default class SiteFooter {
    constructor(element) {
        this.el = element;
        this.isMobile = window.matchMedia('(max-width: 768px)').matches;

        if (!this.isMobile) {
            return;
        }

        this.menuEls = this.el.querySelectorAll('.menu-list[aria-hidden="true"]');
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
                    }
                }
            });
        }
    }
}
