import ExpandableElement from '../../../scripts/helpers/ExpandableElement.js';

export default class Accordion {
    constructor(element) {
        this.el = element;

        this.items = this.el.querySelectorAll('.js-expandable-element');

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

            if (!expandableElement.isExpanded()) {
                item.style.height = '0px'; // set height to ensure initial transition works.
            }
        });
    }
}
