import ExpandableElement from '../../../scripts/helpers/ExpandableElement.js';

export default class Accordion {
    constructor(element) {
        this.el = element;

        this.items = this.el.querySelectorAll('.js-expandable-element');
        this.allowMultiple = this.el.dataset.allowMultiple === 'true';
        this.expandableElements = [];

        // Bail early - no accordion items.
        if (this.items.length < 1) {
            return;
        }

        this.init();
    }

    init() {
        this.items.forEach((item) => {
            const expandableElement = new ExpandableElement(item, {
                setHiddenAttribute: false, // handled manually below.
                on: {
                    expandbegin: () => {
                        // If allow_multiple is false, close all other accordion items before expanding this one
                        if (!this.allowMultiple) {
                            this.closeOtherItems(expandableElement);
                        }

                        item.removeAttribute('hidden');
                    },
                    expandend: () => {
                        item.style.height = `${item.scrollHeight}px`;
                    },
                    collapsebegin: () => {
                        // Listen for 'transitionend' once to set hidden attribute after height has transitioned.
                        item.addEventListener('transitionend', () => item.setAttribute('hidden', true), {
                            once: true,
                        });

                        item.style.height = '0px';
                    },
                },
            });

            // Store reference to expandable element for managing multiple items
            this.expandableElements.push(expandableElement);

            if (!expandableElement.isExpanded()) {
                expandableElement.collapse();
            } else {
                expandableElement.expand();
            }
        });
    }

    closeOtherItems(currentElement) {
        this.expandableElements.forEach((expandableElement) => {
            if (expandableElement !== currentElement && expandableElement.isExpanded()) {
                expandableElement.collapse();
            }
        });
    }
}
