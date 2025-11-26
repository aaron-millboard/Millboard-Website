import debounce from 'lodash.debounce';

/**
 * Timeline component with scroll-based animations and sticky navigation.
 */
export default class Timeline {
    /**
     * @param {HTMLElement} element The timeline container element.
     */
    constructor(element) {
        this.element = element;
        this.items = Array.from(this.element.querySelectorAll('.timeline__item'));
        this.lineContainer = this.element.querySelector('.timeline__line-container');
        this.defaultLine = this.element.querySelector('.timeline__line--default');
        this.activeLine = this.element.querySelector('.timeline__line--active');
        this.navItems = Array.from(this.element.querySelectorAll('.timeline__nav__item'));
        this.navInner = this.element.querySelector('.timeline__nav__inner');

        if (!this.items.length || !this.activeLine) return;

        this.scrollHandler = this.handleScroll.bind(this);
        this.debouncedScrollHandler = debounce(this.scrollHandler, 16);
        
        this.init();
    }

    /**
     * Initialize the component
     */
    init() {
        // Set up navigation click handlers
        this.navItems.forEach((navItem) => {
            navItem.addEventListener('click', this.handleNavClick.bind(this));
        });

        // Set up scroll handler
        window.addEventListener('scroll', this.debouncedScrollHandler, { passive: true });

        // Initial calculation
        this.handleScroll();
    }

    /**
     * Handle scroll event to update active line and dots
     */
    handleScroll() {
        if (!this.lineContainer || !this.activeLine) return;

        const windowScrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const windowHeight = window.innerHeight / 2;
        const lineContainerTop = this.lineContainer.getBoundingClientRect().top + windowScrollTop;
        const lineContainerHeight = this.lineContainer.offsetHeight;

        // Calculate the line height
        if (windowScrollTop >= lineContainerTop - windowHeight) {
            const lineHeight = Math.min(
                windowScrollTop - lineContainerTop + windowHeight,
                lineContainerHeight
            );

            this.activeLine.style.height = `${lineHeight}px`;
        } else {
            this.activeLine.style.height = '0px';
        }

        // Update active items and navigation
        const activeLineBottom = this.activeLine.getBoundingClientRect().bottom + windowScrollTop;
        
        this.items.forEach((item, index) => {
            const marker = item.querySelector('.timeline__item-marker');
            if (!marker) return;

            const markerTop = marker.getBoundingClientRect().top + windowScrollTop;

            if (activeLineBottom > markerTop) {
                item.classList.add('timeline__item--active');
                this.updateActiveNavItem(index);
            } else {
                item.classList.remove('timeline__item--active');
            }
        });
    }

    /**
     * Update active navigation item
     * @param {number} index Index of the active item
     */
    updateActiveNavItem(index) {
        // Remove active class from all nav items
        this.navItems.forEach((navItem) => {
            navItem.classList.remove('timeline__nav__item--active');
        });

        // Add active class to current nav item
        if (this.navItems[index]) {
            this.navItems[index].classList.add('timeline__nav__item--active');

            // Scroll active nav item into view on mobile
            if (window.innerWidth < 768 && this.navInner) {
                this.scrollNavItemIntoView(this.navItems[index]);
            }
        }
    }

    /**
     * Scroll navigation item to center on mobile
     * @param {HTMLElement} navItem Navigation item element
     */
    scrollNavItemIntoView(navItem) {
        const navContainer = this.element.querySelector('.timeline__nav');
        if (!navContainer) return;

        const navItemLeft = navItem.offsetLeft;
        const navItemWidth = navItem.offsetWidth;
        const navContainerWidth = navContainer.offsetWidth;

        // Calculate scroll position to center the item
        const scrollLeft = navItemLeft - (navContainerWidth / 2) + (navItemWidth / 2);

        navContainer.scrollTo({
            left: scrollLeft,
            behavior: 'smooth'
        });
    }

    /**
     * Handle navigation item click
     * @param {Event} event Click event
     */
    handleNavClick(event) {
        event.preventDefault();

        const navItem = event.currentTarget;
        
        // Check if this is the skip button
        if (navItem.dataset.skip === 'true') {
            this.scrollToEnd();
            return;
        }

        const targetId = navItem.dataset.target;
        const targetElement = document.getElementById(targetId);

        if (!targetElement) return;

        // Calculate scroll position
        const targetTop = targetElement.getBoundingClientRect().top + window.pageYOffset;
        const offset = window.innerHeight / 3;

        window.scrollTo({
            top: targetTop - offset,
            behavior: 'smooth'
        });
    }

    /**
     * Scroll to the end of the timeline block
     */
    scrollToEnd() {
        const timelineBottom = this.element.getBoundingClientRect().bottom + window.pageYOffset;

        window.scrollTo({
            top: timelineBottom,
            behavior: 'smooth'
        });
    }

    /**
     * Cleanup when component is destroyed
     */
    destroy() {
        window.removeEventListener('scroll', this.debouncedScrollHandler);
        
        this.navItems.forEach((navItem) => {
            navItem.removeEventListener('click', this.handleNavClick);
        });
    }
}
