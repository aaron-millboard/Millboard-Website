/**
 * Sample basket - toggles free samples in and out of the WooCommerce basket
 * without a page reload.
 *
 * Sample category archives are 20+ screens tall on mobile. Adding a sample used
 * to be a plain ?add-to-cart link, so each of the three picks cost a full page
 * load and dropped the visitor back at the top of the page. This keeps them
 * where they are and shows a running count instead.
 */
export default class SampleBasket {
    /**
     * @param {object} config Localised settings from PHP.
     */
    constructor(config) {
        this.config = config;
        this.buttons = Array.from(document.querySelectorAll('[data-sample-product-id]'));

        if (!this.buttons.length) return;

        // The "add" markup differs per sample (size, dimensions, price), so cache
        // it up front rather than trying to rebuild it in JS.
        this.addStateMarkup = new Map();
        this.buttons.forEach((button) => {
            if (button.dataset.sampleAction === 'add') {
                this.addStateMarkup.set(button.dataset.sampleProductId, button.innerHTML);
            }
        });

        this.bar = null;
        this.pending = false;

        this.init();
    }

    init() {
        this.buttons.forEach((button) => {
            button.addEventListener('click', this.handleClick.bind(this));
        });

        this.buildBar();
        this.syncBar();
    }

    /**
     * @param {MouseEvent} event Click on a sample button.
     */
    async handleClick(event) {
        const button = event.currentTarget;

        // Let the plain link work if the script can't do its job.
        if (!this.config || !this.config.ajaxUrl) return;

        event.preventDefault();

        if (this.pending) return;

        const productId = button.dataset.sampleProductId;
        const toggle = button.dataset.sampleAction === 'remove' ? 'remove' : 'add';

        this.setPending(button, true);

        try {
            const body = new FormData();
            body.append('action', 'granola_sample_toggle');
            body.append('nonce', this.config.nonce);
            body.append('product_id', productId);
            body.append('toggle', toggle);

            const response = await fetch(this.config.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body,
            });

            const payload = await response.json();

            if (!payload || !payload.success) {
                const message = (payload && payload.data && payload.data.message) || this.config.i18n.error;

                // The basket is full, so reflect the real state we were given.
                if (payload && payload.data && payload.data.samples) {
                    this.applyState(payload.data.samples);
                }

                this.announce(message, true);
                return;
            }

            this.applyState(payload.data);
        } catch (error) {
            // Fall back to the plain link so the visitor is never stuck.
            window.location.href = button.getAttribute('href');
        } finally {
            this.setPending(button, false);
        }
    }

    /**
     * @param {HTMLElement} button Button being toggled.
     * @param {boolean} pending Whether a request is in flight.
     */
    setPending(button, pending) {
        this.pending = pending;
        button.classList.toggle('product-samples__button--pending', pending);
        button.setAttribute('aria-busy', pending ? 'true' : 'false');
    }

    /**
     * Flip every sample button on the page to match the basket.
     *
     * @param {{samples: object, count: number, full: boolean}} state Basket state.
     */
    applyState(state) {
        const samples = state.samples || {};

        this.buttons.forEach((button) => {
            const productId = button.dataset.sampleProductId;
            const position = samples[productId];

            if (position) {
                this.renderInCart(button, position);
            } else {
                this.renderAddable(button);
            }
        });

        this.count = state.count || 0;
        this.full = !!state.full;
        this.syncBar();
    }

    /**
     * @param {HTMLElement} button Button to render as already chosen.
     * @param {number} position Place in the basket.
     */
    renderInCart(button, position) {
        button.classList.add('product-samples__button--in-cart');
        button.dataset.sampleAction = 'remove';

        button.innerHTML =
            '<span>' +
            `<span class="product-samples__button__content product-samples__button__content--added">${this.escape(
                this.config.i18n.added,
            )}</span> ` +
            `<span class="product-samples__button__action">${this.escape(
                this.config.i18n.remove.replace('{position}', position),
            )}</span>` +
            '</span>';
    }

    /**
     * @param {HTMLElement} button Button to restore to its addable state.
     */
    renderAddable(button) {
        const original = this.addStateMarkup.get(button.dataset.sampleProductId);

        button.classList.remove('product-samples__button--in-cart');
        button.dataset.sampleAction = 'add';

        if (original) {
            button.innerHTML = original;
        }
    }

    /**
     * Build the sticky progress bar once, so visitors can reach the basket from
     * anywhere on a very long page.
     */
    buildBar() {
        if (!this.config.cartUrl) return;

        this.bar = document.createElement('div');
        this.bar.className = 'product-samples__bar';
        this.bar.setAttribute('hidden', 'hidden');
        this.bar.innerHTML =
            '<p class="product-samples__bar__count" role="status" aria-live="polite"></p>' +
            `<a class="g-button g-button--primary product-samples__bar__link" href="${this.escape(
                this.config.cartUrl,
            )}">${this.escape(this.config.i18n.viewBasket)}</a>`;

        document.body.appendChild(this.bar);

        this.count = this.buttons.filter((button) => button.dataset.sampleAction === 'remove').length;
    }

    /**
     * Show or hide the sticky bar and keep its count current.
     */
    syncBar() {
        if (!this.bar) return;

        const count = this.count || 0;

        if (!count) {
            this.bar.setAttribute('hidden', 'hidden');
            return;
        }

        this.bar.removeAttribute('hidden');
        this.bar.querySelector('.product-samples__bar__count').textContent = this.config.i18n.chosen.replace(
            '{count}',
            count,
        );
    }

    /**
     * Surface a message without an alert box.
     *
     * @param {string} message Text to show.
     * @param {boolean} isError Whether it is a failure.
     */
    announce(message, isError = false) {
        if (!this.bar) return;

        this.bar.removeAttribute('hidden');
        this.bar.classList.toggle('product-samples__bar--error', isError);

        const target = this.bar.querySelector('.product-samples__bar__count');
        target.textContent = message;

        window.clearTimeout(this.announceTimer);
        this.announceTimer = window.setTimeout(() => {
            this.bar.classList.remove('product-samples__bar--error');
            this.syncBar();
        }, 4000);
    }

    /**
     * @param {string} value Untrusted string destined for innerHTML.
     * @returns {string} Escaped string.
     */
    escape(value) {
        const div = document.createElement('div');
        div.textContent = String(value == null ? '' : value);
        return div.innerHTML;
    }
}
