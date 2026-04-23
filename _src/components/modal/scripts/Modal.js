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
                // Prevent the modal from closing for all links
                event.stopPropagation();
    
                // Allow the link to work normally
                if (!link.hasAttribute('data-close-modal')) {
                    return;
                }
    
                // If the link has the data-close-modal attribute, close the modal
                event.preventDefault();
                this.close();
                window.location.href = link.href;
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