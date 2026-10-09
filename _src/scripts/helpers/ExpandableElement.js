import Focusable from './Focusable.js';

/**
 * ExpandableElement
 *
 * Give the target element:
 * - [id="TARGET ELEMENT ID"]
 *
 * Give all trigger elements:
 * - [aria-controls="TARGET ELEMENT ID"]
 *
 * a11y overview of aria-expanded: https://www.accessibility-developer-guide.com/examples/sensible-aria-usage/expanded/
 */
export default class ExpandableElement {
    constructor(target, options = {}) {
        // Bail early - required elements or markup not found.
        if (!this.setUpRequiredElements(target)) {
            return;
        }

        // Set instance options by combining default options with any overrides via spread syntax.
        this.options = {
            collapseOnEscape: true,
            collapseOnFocusout: false,
            collapseAncestorsOnEscape: false,
            collapseOnAncestorCollapse: true,
            focusWithinOnExpand: false,
            updateChildTabIndexes: true,
            setHiddenAttribute: true,
            on: {},
            ...options,
        };

        this.lastTrigger = null;

        // Helper object to manage focusable elements inside expandable element.
        this.focusableItems = new Focusable(this.targetElement);

        // Handle expanding.
        this.targetElement.addEventListener('expandbegin', this);
        this.targetElement.addEventListener('expandend', this);

        // Handle clicks - triggers and inside & outside target.
        document.addEventListener('click', this);
    }

    updateConfig(newConfig) {
        this.options = { ...this.options, ...newConfig };
    }

    handlePotentialFocusLoss(event) {
        if (!event.relatedTarget) {
            return;
        }

        if (this.targetElement.contains(event.relatedTarget)) {
            return;
        }

        if ([...this.triggerElements].includes(event.relatedTarget)) {
            return;
        }

        this.collapse();
    }

    isExpanded() {
        return !this.targetElement.hasAttribute('aria-hidden');
    }

    expand() {
        this.targetElement.dispatchEvent(ExpandableElement.events.expandbegin);

        this.targetElement.removeAttribute('aria-hidden');

        if (this.options.setHiddenAttribute === true) {
            this.targetElement.removeAttribute('hidden');
        }

        this.triggerElements.forEach((trigger) => trigger.setAttribute('aria-expanded', 'true'));

        if (this.options.updateChildTabIndexes === true) {
            this.focusableItems.resetTabIndex();
        }

        this.targetElement.dispatchEvent(ExpandableElement.events.expandend);
    }

    collapse() {
        this.targetElement.dispatchEvent(ExpandableElement.events.collapsebegin);

        this.targetElement.setAttribute('aria-hidden', 'true');

        if (this.options.setHiddenAttribute === true) {
            this.targetElement.setAttribute('hidden', 'hidden');
        }

        this.triggerElements.forEach((trigger) => trigger.setAttribute('aria-expanded', 'false'));

        if (this.options.updateChildTabIndexes === true) {
            this.focusableItems.hideAllFromKeyboard();
        }

        this.targetElement.dispatchEvent(ExpandableElement.events.collapseend);
    }

    toggle() {
        if (this.isExpanded()) {
            this.collapse();
        } else {
            this.expand();
        }
    }

    /**
     * Sets the ExpandableElement's required elements - a target and at least one trigger - returning success/failure.
     *
     * @param {node} target The required triggering element.
     * @returns {boolean} Whether setting up the required elements was successful.
     */
    setUpRequiredElements(target) {
        this.targetElement = target;

        // Bail early - invalid target element passed.
        if (!this.targetElement || !(this.targetElement instanceof HTMLElement)) {
            console.error('Invalid target element', this.targetElement, this);
            return false;
        }

        if (!this.targetElement.hasAttribute('id') || this.targetElement.id === '') {
            console.error('Target element missing required "id" attribute', this.targetElement, this);
            return false;
        }

        this.triggerElements = document.querySelectorAll(`[aria-controls=${this.targetElement.id}]`);

        // Bail early - invalid trigger element passed.
        if (!this.triggerElements || this.triggerElements.length < 1) {
            console.error('No trigger elements found', this.triggerElements, this);
            return false;
        }

        const isExpanded = this.isExpanded() ? 'true' : 'false';
        this.triggerElements.forEach((trigger) => {
            // Improve accessibility of trigger element if it isn't a <button>.
            if (trigger.tagName !== 'BUTTON') {
                trigger.setAttribute('role', 'button');
            }

            // Ensure required accessibility attribute is set.
            trigger.setAttribute('aria-expanded', isExpanded);
        });

        return true;
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

        // add event listeners from 'on' options
        Object.keys(this.options.on).forEach((eventName) => {
            if (eventName === event.type) {
                this.options.on[eventName](event);
            }
        });
    }

    onclick(event) {
        if ([...this.triggerElements].includes(event.target)) {
            this.lastTrigger = event.target;
            this.toggle();
        } else if (
            this.options.collapseOnFocusout === true &&
            this.isExpanded() &&
            !this.targetElement.contains(event.target)
        ) {
            this.collapse();
        }
    }

    onfocusout(event) {
        this.handlePotentialFocusLoss(event);
    }

    onblur(event) {
        this.handlePotentialFocusLoss(event);
    }

    onkeydown(event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (!this.targetElement.contains(event.target)) {
            return;
        }

        this.collapse();

        // Replace focus for keydown events.
        if (this.lastTrigger) {
            this.lastTrigger.focus();
            this.lastTrigger = null;
        }

        // Conditionally prevent ancestor elements from collapsing.
        if (this.options.collapseAncestorsOnEscape === false) {
            event.stopPropagation();
        }
    }

    oncollapsebegin() {
        this.targetElement.removeEventListener('collapsebegin', this);

        if (this.options.collapseOnFocusout === true) {
            this.targetElement.removeEventListener('focusout', this);
            this.triggerElements.forEach((trigger) => trigger.removeEventListener('blur', this));
        }

        if (this.options.collapseOnEscape === true) {
            this.targetElement.removeEventListener('keydown', this);
        }

        if (this.options.collapseOnAncestorCollapse === true) {
            document.removeEventListener('collapseend', this);
        }
    }

    oncollapseend({ target }) {
        if (target === this.targetElement) {
            // Stop handling ExpandableElement collapse.
            this.targetElement.removeEventListener('collapseend', this);

            // Handle ExpandableElement expand.
            this.targetElement.addEventListener('expandbegin', this);
            this.targetElement.addEventListener('expandend', this);
        } else if (this.options.collapseOnAncestorCollapse === true && target.contains(this.targetElement)) {
            this.collapse();
        }
    }

    onexpandbegin() {
        this.targetElement.removeEventListener('expandbegin', this);
    }

    onexpandend() {
        // Stop handling ExpandableElement expand.
        this.targetElement.removeEventListener('expandend', this);

        // Start handling ExpandableElement collapse.
        this.targetElement.addEventListener('collapsebegin', this);
        this.targetElement.addEventListener('collapseend', this);

        if (this.options.focusWithinOnExpand === true) {
            window.setTimeout(() => {
                // If the parent element was display:none, focus must be set after the parent element displays.
                this.focusableItems.firstFocusable.focus();
            }, 100);
        }

        if (this.options.collapseOnFocusout === true) {
            this.targetElement.addEventListener('focusout', this);
            this.triggerElements.forEach((trigger) => trigger.addEventListener('blur', this));
        }

        if (this.options.collapseOnEscape === true) {
            this.targetElement.addEventListener('keydown', this);
        }

        if (this.options.collapseOnAncestorCollapse === true) {
            document.addEventListener('collapseend', this);
        }
    }

    // Custom events for ExpandableElement state change listeners.
    static events = {
        get collapsebegin() {
            return new Event('collapsebegin');
        },
        get collapseend() {
            return new Event('collapseend', { bubbles: true });
        },
        get expandbegin() {
            return new Event('expandbegin');
        },
        get expandend() {
            return new Event('expandend', { bubbles: true });
        },
    };
}

// Class custom events - standardised for use elsewhere.
export const { events } = ExpandableElement;
