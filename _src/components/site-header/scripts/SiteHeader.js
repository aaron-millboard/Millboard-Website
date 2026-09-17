import debounce from 'lodash.debounce';
import isElementVisible from '../../../scripts/helpers/isElementVisible.js';
import ExpandableElement from '../../../scripts/helpers/ExpandableElement.js';

// TODO manage focus leaving overlay mobile menu and close it (or trap it)

export default class SiteHeader {
    constructor(element) {
        this.el = element;
        this.body = document.querySelector('body');
        this.innerEl = this.el.querySelector('.site-header__inner');
        this.navigationEl = this.el.querySelector('.site-header__navigation');
        this.mainMenuEl = this.el.querySelector('#main-menu');
        this.burgerEl = this.el.querySelector('.site-header__burger');
        this.headerTogglerEls = this.el.querySelectorAll('.js-site-header-toggle');
        this.searchEl = this.el.querySelector('.header-search');
        this.utilityEl = this.el.querySelector('.site-header__utility');
        this.callToActionEl = this.el.querySelector('.site-header__call-to-action-1');
        this.currentPageAnchorEls = this.el.querySelectorAll('.current-menu-item > [href*="#"]');

        // Every top-level link in the primary nav, across BOTH halves. The
        // wordmark sits between them, so the menu renders twice -- scoping this
        // to #main-menu alone would leave the second half's dropdowns dead.
        this.primaryMenuEls = this.el.querySelectorAll('.site-header__navigation--primary .menu-list');

        // Sub-menus are collected from the whole header for the same reason.
        this.subMenuExpandableEls = this.el.querySelectorAll(
            '.site-header__navigation--primary .js-expandable-element'
        );

        // Stores the sub-menu ExpandableElement instances and the parent menu item for hover triggering.
        this.subMenuDropdowns = {};

        this.init();
    }

    init() {
        this.setHeight();
        this.initScrollState();

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

        // Escape closes the drawer, returning focus to the burger.
        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.el.classList.contains('is-open')) {
                this.closeHeader();
            }
        });

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

    /**
     * Drives the header's scroll state as a single custom property, `--nav`,
     * running 0 -> 1 across the first 90px of the page.
     *
     * Both row heights, the wordmark's size and the utility strip's collapse
     * all interpolate from this one number in CSS, so there is no class to keep
     * in step and no second definition of "scrolled". The homepage hero reads
     * the same variable, which is why it is set on the document element.
     *
     * Written inside requestAnimationFrame off a passive listener: the handler
     * only ever reads scrollY and writes a custom property, so there is no
     * layout read per frame.
     */
    initScrollState() {
        this.navRaf = null;

        this.writeNavState = () => {
            this.navRaf = null;

            const scrollTop = window.scrollY || document.documentElement.scrollTop || 0;

            document.documentElement.style.setProperty('--nav', Math.min(1, scrollTop / 90).toFixed(3));

            // The utility strip collapses by clipping its own overflow, which
            // also clips anything trying to escape it -- the locale dropdown
            // opens 108px tall out of a 44px strip and lost 96px of itself.
            //
            // It only needs to clip while it is actually collapsing, so the
            // clip goes on the moment the page moves and comes off at rest.
            // At rest there is nothing to clip: 44px of content inside a
            // 150px max-height.
            this.el.classList.toggle('site-header--scrolled', scrollTop > 0);
        };

        this.onNavScroll = () => {
            if (!this.navRaf) {
                this.navRaf = requestAnimationFrame(this.writeNavState);
            }
        };

        window.addEventListener('scroll', this.onNavScroll, { passive: true });
        this.writeNavState();
    }

    getHeight() {
        // The header element itself, so the utility strip is included -- it is a
        // sibling of the main row, not a child of it, and measuring only the row
        // reported 88px for a 132px header. The search panel is absolutely
        // positioned and so contributes nothing here, which is what we want.
        return this.el.offsetHeight;
    }

    /**
     * Write the header's height out for anything that positions against it.
     *
     * Called on load, on resize and when the drawer closes -- deliberately NOT
     * on scroll. It used to run on every scroll frame, on the reasoning that
     * the header's height changes with `--nav` so the offset should follow.
     *
     * It does change, and following it that way made the page shudder. The
     * header is in flow, so its collapse lifts everything below it, and the
     * home hero cancels that with a negative top margin built from this value.
     * Measuring mid-collapse and writing the result fed a number that was
     * always slightly behind the height it was meant to cancel, and worse, the
     * sampling stopped the moment scrolling did while the collapse carried on.
     * Anything that needs the live height should read `--site-header--height`,
     * which is the same collapse expressed in CSS and is therefore never out
     * of step with it.
     */
    setHeight() {
        // The viewport's real width, excluding the scrollbar. Full-bleed
        // dropdowns size from this rather than 100vw, which counts the
        // scrollbar and so overhangs the page by its width. Written before the
        // guard below, because it is true whatever the drawer is doing.
        document.documentElement.style.setProperty(
            '--site-header--viewport',
            `${document.documentElement.clientWidth}px`
        );

        // While the drawer is open the inner element is the full-height panel,
        // which is not the header's resting height -- measuring it would push
        // every sticky offset on the page down by a screenful.
        if (this.el.classList.contains('is-open')) {
            return;
        }

        this.headerHeight = this.getHeight();

        document.documentElement.style.setProperty('--site-header--bottom', `${this.headerHeight}px`);

        // The utility strip's natural height, which is what it collapses FROM.
        //
        // The cap has to come off to measure it, because the cap is built from
        // this very number. scrollHeight looks like the way round that and is
        // not: the strip is deliberately unclipped at rest so the locale
        // dropdown can open out of it, and scrollHeight counts that dropdown.
        // It reported 286px for a 44px strip, which put the whole collapse in
        // the last seventh of the scroll and left the header lurching.
        //
        // Only on wide viewports, where the strip is part of the header rather
        // than a block in the drawer; on compact it is display: none and would
        // measure zero.
        if (this.utilityEl && this.utilityEl.offsetParent !== null) {
            this.utilityEl.style.maxHeight = 'none';
            const stripHeight = this.utilityEl.offsetHeight;
            this.utilityEl.style.maxHeight = '';

            document.documentElement.style.setProperty('--site-header--strip', `${stripHeight}px`);
        }

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

        // Across both halves of the primary nav, in DOM order, so the first
        // link focused is the first one in the menu.
        this.primaryMenuEls.forEach((list) => {
            Array.from(list.children).forEach((li) => {
                const a = li.querySelector('a');

                if (!a) {
                    return;
                }

                SiteHeader.setTabIndex([a], 0);

                if (first === '') {
                    first = a;
                }
            });
        });

        this.el.classList.add('is-open');

        // The drawer scrolls itself, so the page behind it must not.
        this.body.style.overflow = 'hidden';

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
        this.body.style.overflow = '';

        // The resting height is only measurable once the drawer has closed.
        this.setHeight();

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
