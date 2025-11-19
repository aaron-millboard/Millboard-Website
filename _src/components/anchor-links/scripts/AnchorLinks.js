import ExpandableElement from '../../../scripts/helpers/ExpandableElement.js';

export default class AnchorLinks {
    constructor(element) {
        this.el = element;

        // Y scroll values.
        this.elHeight = this.el?.offsetHeight || 60;
        const initialOffsetTop = this.el.getBoundingClientRect().top + window.scrollY;
        this.elInitialOffsetTop = initialOffsetTop + this.elHeight;
        this.previousScrollY = window.scrollY; // Holds our previous scroll position to work out if we're going up/down.

        // Expander.
        this.expandableEl = this.el.querySelector('.js-expandable-element');
        this.expandableElement = null;

        this.init();
    }

    init() {
        if (this.expandableEl) {
            this.expandableElement = new ExpandableElement(this.expandableEl, {
                collapseOnFocusout: true,
            });

            this.expandableEl.addEventListener('click', this.handleClick.bind(this));
        }

        // Add scroll listener.
        window.addEventListener(
            'scroll',
            () => {
                this.checkStickyAndScroll();
            },
            { passive: true }
        );
    }

    /**
     * Handle the click event and collapse the expandable element if a link is clicked.
     * @param {object} event - The click event.
     */
    handleClick(event) {
        if (event.target.tagName === 'A') {
            this.expandableElement.collapse();
        }
    }

    /**
     * Checks if our element should be hidden.
     * Is it stuck (position to top less than 1)
     * Has it scrolled the full length past the element: the initial position which includes height of nav bar
     *         > window scrollY
     * And is scrolling downwards.
     */
    checkStickyAndScroll() {
        const { scrollY } = window;
        const rect = this.el.getBoundingClientRect();
        const isStuck = rect.top < 0;
        const hasScrolledPastNavbar = scrollY > this.elInitialOffsetTop;
        const isScrollingDown = scrollY > this.previousScrollY;

        // Add hidden attr if stuck and scrolled down past navbar
        if (isStuck && hasScrolledPastNavbar && isScrollingDown) {
            if (this.expandableElement.isExpanded()) {
                return; // Don't hide if expanded.
            }

            this.el.setAttribute('hidden', 'hidden');
        }

        // Remove hidden attr if scrolling up
        if (!isScrollingDown || scrollY <= this.elHeight) {
            this.el.removeAttribute('hidden');
        }

        this.previousScrollY = scrollY;
    }
}
