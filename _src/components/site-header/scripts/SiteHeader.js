import debounce from 'lodash.debounce';
import isElementVisible from '../../../scripts/helpers/isElementVisible.js';
import ExpandableElement from '../../../scripts/helpers/ExpandableElement.js';

// TODO manage focus leaving overlay mobile menu and close it (or trap it)
// TODO add escape key suppport for submenus and mobile menu

export default class SiteHeader {
    constructor(element) {
        this.el = element;
        this.body = document.querySelector('body');
        this.headerTopEl = this.el.querySelector('.site-header__top');
        this.navigationEl = this.el.querySelector('.site-header__navigation');
        this.mainMenuEl = this.el.querySelector('#main-menu');
        this.burgerEl = this.el.querySelector('.site-header__burger');
        this.headerTogglerEls = this.el.querySelectorAll('.js-site-header-toggle');
        this.searchEl = this.el.querySelector('.header-search');
        this.callToActionEl = this.el.querySelector('.site-header__call-to-action-1');
        this.currentPageAnchorEls = this.el.querySelectorAll('.current-menu-item > [href*="#"]');

        this.subMenuExpandableEls = {};
        if (this.mainMenuEl) {
            this.subMenuExpandableEls = this.mainMenuEl.querySelectorAll('.js-expandable-element');
        }

        // Stores the sub-menu ExpandableElement instances and the parent menu item for hover triggering.
        this.subMenuDropdowns = {};

        this.init();
    }

    init() {
        this.setHeight();

        window.addEventListener(
            'resize',
            debounce(() => {
                this.setHeight();
                this.updateSubMenuDropdowns();

                if (!this.isBurgerModeActive()) {
                    this.closeHeader(true);
                }
            }, 50)
        );

        // Listen to custom scroll events.
        window.addEventListener('scrollchange', this);
        window.addEventListener('scrolldown', this);

        if (this.isBurgerModeActive()) {
            this.closeHeader(true);
        }

        // ---------------------------------------------------------------------
        // Handle the toggler elements that will open and close the menu.
        // ---------------------------------------------------------------------
        if (this.headerTogglerEls.length > 0) {
            this.headerTogglerEls.forEach((toggle) => {
                toggle.addEventListener('click', () => {
                    this.toggleHeader();
                });
            });
        }

        // ---------------------------------------------------------------------
        // Handle anchor links in the same page. Close the mobile menu if it is open.
        // ---------------------------------------------------------------------
        if (this.currentPageAnchorEls.length > 0) {
            this.currentPageAnchorEls.forEach((link) => {
                link.addEventListener('click', () => {
                    this.closeHeader(true);
                });
            });
        }

        // ---------------------------------------------------------------------
        // Track clicks on the header call to action.
        // ---------------------------------------------------------------------
        if (this.callToActionEl) {
            this.callToActionEl.addEventListener('click', () => {
                this.pushCallToActionClick();
            });
        }

        // ---------------------------------------------------------------------
        // Set up sub menu expanding/collapsing functionality using ExpandableElement.
        // ---------------------------------------------------------------------
        if (this.subMenuExpandableEls.length > 0) {
            this.subMenuExpandableEls.forEach((element) => {
                if (element instanceof Element) {
                    const expandableEl = new ExpandableElement(element, {
                        collapseOnFocusout: !this.isBurgerModeActive(),
                    });

                    const dropdown = {
                        element,
                        expandableEl,
                        parent: element.closest('.menu-item'),
                    };

                    // Add hover listeners to dropdown parents
                    this.subMenuDropdowns[element.id] = dropdown;

                    // Only add hover actions to top level submenus
                    if (!element.classList.contains('sub-menu--depth-0')) {
                        return;
                    }

                    expandableEl.collapse();

                    const linkEl = dropdown.parent.querySelector('a');
                    const subMenuLinks = dropdown.parent.querySelectorAll('.sub-menu a');

                    dropdown.parent.addEventListener('mouseenter', (e) => this.handleSubMenuParentEvent(e));
                    dropdown.parent.addEventListener('mouseleave', (e) => this.handleSubMenuParentEvent(e));

                    if (linkEl) {
                        linkEl.addEventListener('focusin', (e) => this.handleSubMenuParentEvent(e));
                        linkEl.addEventListener('focusout', (e) => this.handleSubMenuParentEvent(e));
                    }

                    // Add focus listeners to all submenu links
                    subMenuLinks.forEach((link) => {
                        link.addEventListener('focusin', (e) => this.handleSubMenuParentEvent(e));
                        link.addEventListener('focusout', (e) => this.handleSubMenuParentEvent(e));
                    });
                }
            });
        }

        // ---------------------------------------------------------------------
        // Set up our search input expanding/collapsing functionality using ExpandableElement.
        // ---------------------------------------------------------------------
        if (this.searchEl) {
            new ExpandableElement(this.searchEl, {
                collapseOnFocusout: true,
                focusWithinOnExpand: true,
                on: {
                    expandend: () => {
                        this.body.classList.add('is-show-backdrop');
                    },
                    collapseend: () => {
                        this.body.classList.remove('is-show-backdrop');
                    },
                },
            });
        }

        this.el.addEventListener('collapseend', (e) => {
            this.body.classList.remove('is-site-header-submenu-expanded');

            const parentItem = e.target.closest('.menu-item');

            if (parentItem) {
                parentItem.classList.remove('is-submenu-expanded');
            }
        });

        this.el.addEventListener('expandend', (e) => {
            this.body.classList.add('is-site-header-submenu-expanded');

            const parentItem = e.target.closest('.menu-item');

            if (parentItem) {
                parentItem.classList.add('is-submenu-expanded');
            }
        });
    }

    /**
     * Tracks clicks on the header call to action by pushing a `header_cta_click`
     * custom event to the GTM dataLayer (which forwards it to GA4), the same way
     * the map and partner phone reveal report their clicks.
     *
     * `link_url` is already a registered GA4 custom dimension, so the
     * destination is reportable without any new setup; the label and locale are
     * both recoverable from the page path, so neither is sent.
     */
    pushCallToActionClick() {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: 'header_cta_click',
            link_url: this.callToActionEl.getAttribute('href') || '',
        });
    }

    getHeight() {
        const headerHeight = this.headerTopEl.offsetHeight;

        // if (this.announcementBanner) {
        //     headerHeight += this.announcementBanner.offsetHeight;
        // }

        return headerHeight;
    }

    setHeight() {
        this.headerHeight = this.getHeight();

        document.documentElement.style.setProperty('--site-header--bottom', `${this.headerHeight}px`);
        this.el.classList.add('site-header--positioned');
    }

    toggleHeader() {
        if (this.el.classList.contains('is-open')) {
            this.closeHeader();
        } else {
            this.openHeader();
        }
    }

    openHeader() {
        let first = '';

        if (this.mainMenuEl) {
            const listItems = Array.from(this.mainMenuEl.children);

            listItems.forEach((li) => {
                const a = li.querySelector('a');

                SiteHeader.setTabIndex([a], 0);

                if (first === '') {
                    first = a;
                }
            });
        }

        this.el.classList.add('is-open');

        this.headerTogglerEls.forEach((toggle) => {
            toggle.setAttribute('aria-expanded', 'true');
        });

        SiteHeader.setTabIndex(this.headerTogglerEls, 0);

        // preventScroll so focusing the first link never scrolls the page.
        if (this.mainMenuEl && first) {
            first.focus({ preventScroll: true });
        }
    }

    closeHeader(initial = false) {

        // close the menu
        this.el.classList.remove('is-open');

        if (this.isBurgerModeActive()) {
            this.headerTogglerEls.forEach((toggle) => {
                toggle.setAttribute('aria-expanded', 'false');
            });

            // make the items not tabbable // currently handled by visibility:hidden
            // if (this.navigationEl) {
            //     const elements = SiteHeader.getTabbableItems(this.navigationEl);
            //     SiteHeader.setTabIndex(elements, -1);
            // }

            if (initial !== true) {
                // Focus the burger (preventScroll so it never jumps the page).
                this.burgerEl.focus({ preventScroll: true });
            }
        }
    }

    updateSubMenuDropdowns() {
        if (!this.subMenuDropdowns.length) {
            return;
        }

        this.subMenuDropdowns.forEach((dropdown) => {
            dropdown.expandableEl.updateConfig({
                collapseOnFocusout: !this.isBurgerModeActive(),
            });
        });
    }

    handleSubMenuParentEvent(event) {
        // Ignore submenu parent events if burger mode is active
        if (this.isBurgerModeActive()) {
            return;
        }

        let expandableElTarget;
        let menuItem;

        // For focus events, we need to find the expandable element from the focused link's parent
        if (event.type === 'focusin' || event.type === 'focusout') {
            menuItem = event.target.closest('.menu-item');
            expandableElTarget = menuItem ? menuItem.querySelector('.js-expandable-element') : null;
        } else {
            // For mouse events, the target is the parent menu item
            menuItem = event.target;
            expandableElTarget = event.target.querySelector('.js-expandable-element');
        }

        if (!expandableElTarget) {
            return;
        }

        const { expandableEl } = this.subMenuDropdowns[expandableElTarget.id] ?? {};

        if (!(expandableEl instanceof ExpandableElement)) {
            return;
        }

        // For focusout, check if focus is moving within the submenu
        if (event.type === 'focusout') {
            // Use setTimeout to allow relatedTarget to be set
            setTimeout(() => {
                const newFocusedElement = document.activeElement;
                const isMovingToSubmenu = menuItem && menuItem.contains(newFocusedElement);

                if (!isMovingToSubmenu && expandableEl.isExpanded()) {
                    expandableEl.collapse();
                }
            }, 0);
            return;
        }

        if (event.type === 'mouseleave' && expandableEl.isExpanded()) {
            expandableEl.collapse();
        } else if ((event.type === 'mouseenter' || event.type === 'focusin') && !expandableEl.isExpanded()) {
            expandableEl.expand();
        }
    }

    static setTabIndex(elements, index) {
        elements.forEach((element) => {
            element.tabIndex = index;
        });
    }

    static getTabbableItems(parent) {
        return parent.querySelectorAll('a, button');
    }

    isBurgerModeActive() {
        return isElementVisible(this.burgerEl);
    }

    /**
     * Handle events with class functions to retain class context.
     *
     * @link https://webreflection.medium.com/dom-handleevent-a-cross-platform-standard-since-year-2000-5bf17287fd38
     *
     * @param {Event} event An event object.
     */
    handleEvent(event) {
        this[`on${event.type}`](event);
    }

    onscrolldown() {
        this.body.classList.toggle('scroll-valid', document.documentElement.scrollTop > 64);
    }

    onscrollchange(event) {
        this.body.classList.toggle('scrolling-up', event.detail.direction === 'up');
        this.body.classList.toggle('scrolling-down', event.detail.direction === 'down');
    }
}
