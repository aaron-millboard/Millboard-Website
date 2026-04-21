import { setCookie } from '../../../scripts/helpers/cookies.js';

/**
 * Modal component with open/close functionality and cookie support.
 */
export default class Modal {
    /**
     * @param {HTMLElement} element The modal container element.
     */
    constructor(element) {
        this.element = element;
        this.lockScroll = this.element.dataset.lockScroll === 'true';
        this.cookieSet = false;
        this.init();
    }

    /**
     * Initialize the component
     */
    init() {
        this.initDismissButton();
        this.initOpenButton();
        this.initModalLinks();

        if (this.element.classList.contains('modal--active')) {
            this.open();
        }
    }

    /**
     * Open the modal
     */
    open() {
        this.element.classList.add('modal--active');

        if (this.lockScroll) {
            document.body.style.overflow = 'hidden';
        }
    }

    /**
     * Close the modal and set cookie if configured
     */
    close() {
        this.element.classList.remove('modal--active');

        if (this.lockScroll) {
            document.body.style.overflow = '';
        }

        // Get number of days to set the cookie
        if (this.element.dataset.cookie) {
            const modalId = this.element.getAttribute('id');
            const { hash } = this.element.dataset;
            const days = this.element.dataset.cookie || 3;
            setCookie(modalId, hash, days);
            this.cookieSet = true;
        }
    }

    /**
     * Initialize click handlers for links inside the modal
     */
    initModalLinks() {
        const modalLinks = this.element.querySelectorAll('a[href]');

        modalLinks.forEach((link) => {
            link.addEventListener('click', (event) => {
                // Check if the link has the data-ignore-popup-close attribute
                if (link.hasAttribute('data-ignore-popup-close')) {
                    // Allow the link to work without closing the modal
                    return;
                }

                // Prevent the initial link click.
                event.preventDefault();

                // Close the modal and set cookie if configured
                this.close();

                // Get link URL without hash
                let linkUrl = link.href;

                // Get current URL
                let currentUrl = window.location.href;

                if (linkUrl && currentUrl) {
                    linkUrl = linkUrl.split('/?')[0];
                    currentUrl = currentUrl.split('/?')[0];

                    // Perform click if URL is not current
                    if (linkUrl !== currentUrl) {
                        // This time (as cookie is set) the default action will be performed without interruption if user clicks the link again
                        link.click();
                    }
                }
            });
        });
    }

    /**
     * Initialize dismiss button click handlers
     */
    initDismissButton() {
        const modalDismissers = this.element.querySelectorAll('.modal__dismiss');
        modalDismissers.forEach((modalDismisser) => {
            modalDismisser.addEventListener('click', () => {
                this.close();
            });
        });
    }

    /**
     * Initialize open button click handlers
     */
    initOpenButton() {
        const modalOpeners = document.querySelectorAll(`[data-open-modal="${this.element.id}"]`);
        modalOpeners.forEach((modalOpener) => {
            modalOpener.addEventListener('click', () => {
                this.open();
            });
        });
    }
}