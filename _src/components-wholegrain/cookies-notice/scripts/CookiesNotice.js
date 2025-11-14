import { getCookie } from '../../../scripts/helpers/cookies.js';

class CookiesNotice {
    constructor(element) {
        this.el = element;

        this.bannerPrevActiveElement = document.activeElement;

        this.bannerEl = this.el.querySelector('.cookies-notice__banner');
        this.bannerTogglerEls = document.querySelectorAll('.js-cookies-notice-toggler');

        this.cookiesPreferencesGroupsEl = this.bannerEl.querySelector('.cookies-preferences__consent-groups');

        this.init();
    }

    init() {
        if (getCookie('cookies-consent-preferences-saved') !== '1') {
            this.setBannerActive(true);
        } else {
            this.setBannerActive(false);
        }

        this.bannerTogglerEls.forEach((element) => {
            element.setAttribute('aria-expanded', this.isBannerActive());
            element.setAttribute('aria-controls', this.el.id);
            element.addEventListener('click', this.handleBannerTogglerClick.bind(this));
        });

        this.cookiesPreferencesGroupsEl.addEventListener('expandend', () => {
            this.el.classList.add('cookies-notice--expanded');
        });

        this.cookiesPreferencesGroupsEl.addEventListener('collapsebegin', () => {
            this.el.classList.remove('cookies-notice--expanded');
        });

        document.addEventListener('cookiespreferencessaved', () => {
            this.setBannerActive(false);
        });
    }

    /**
     * Set the active state of the notice
     *
     * @param {boolean} active Whether or not we want to set the notice as active
     */
    setBannerActive(active) {
        if (active === true) {
            this.bannerPrevActiveElement = document.activeElement;
            this.el.removeAttribute('aria-hidden');

            this.bannerEl.focus();

            this.bannerTogglerEls.forEach((element) => {
                element.setAttribute('aria-expanded', true);
            });
        } else {
            // this.bannerPrevActiveElement.focus();
            this.el.setAttribute('aria-hidden', true);

            this.bannerTogglerEls.forEach((element) => {
                element.setAttribute('aria-expanded', false);
            });
        }
    }

    /**
     * Toggle the active state
     *
     */
    toggleBannerActive() {
        this.setBannerActive(!this.isBannerActive());
    }

    /**
     * Check whether the notice is currently active
     *
     */
    isBannerActive() {
        return !this.el.hasAttribute('aria-hidden');
    }

    /**
     * Handle a toggler click
     *
     */
    handleBannerTogglerClick() {
        this.toggleBannerActive();
    }
}

export default CookiesNotice;
