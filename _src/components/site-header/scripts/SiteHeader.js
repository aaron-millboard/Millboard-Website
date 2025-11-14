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
        this.currentPageAnchorEls = this.el.querySelectorAll('.current-menu-item > [href*="#"]');
        this.subMenuExpandableEls = this.mainMenuEl.querySelectorAll('.js-expandable-element');
        this.searchEl = this.el.querySelector('.header-search');

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

                    dropdown.parent.addEventListener('mouseenter', (e) => this.handleSubMenuParentEvent(e));
                    dropdown.parent.addEventListener('mouseleave', (e) => this.handleSubMenuParentEvent(e));
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
        document.documentElement.classList.add('no-scroll');

        this.headerTogglerEls.forEach((toggle) => {
            toggle.setAttribute('aria-expanded', 'true');
        });

        SiteHeader.setTabIndex(this.headerTogglerEls, 0);

        if (this.mainMenuEl) {
            first.focus();
        }
    }

    closeHeader(initial = false) {
        // close the menu
        this.el.classList.remove('is-open');
        document.documentElement.classList.remove('no-scroll');

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
                // Focus the burger
                this.burgerEl.focus();
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

        const expandableElTarget = event.target.querySelector('.js-expandable-element');
        const { expandableEl } = this.subMenuDropdowns[expandableElTarget.id] ?? {};

        if (!(expandableEl instanceof ExpandableElement)) {
            return;
        }

        if (event.type === 'mouseleave' && expandableEl.isExpanded()) {
            expandableEl.collapse();
        } else if (event.type === 'mouseenter' && !expandableEl.isExpanded()) {
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
}
