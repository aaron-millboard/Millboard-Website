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

            // A stale nonce makes WordPress answer with a bare -1 rather than our
            // payload. Treat anything that isn't an object as unrecoverable and
            // let the plain link take over.
            if (!payload || typeof payload !== 'object') {
                window.location.href = button.getAttribute('href');
                return;
            }

            if (!payload.success) {
                // The basket is full, so reflect the real state we were given.
                if (payload.data && payload.data.state) {
                    this.applyState(payload.data.state);
                }

                // A refusal because three free samples are already chosen gets a
                // notice on the button itself, where the visitor is looking.
                if (response.status === 409) {
                    this.showLimitNotice(button, this.config.i18n.limit);
                    this.track('sample_limit_reached', button, payload.data && payload.data.state);
                    return;
                }

                this.announce((payload.data && payload.data.message) || this.config.i18n.error, true);
                return;
            }

            this.applyState(payload.data);
            this.track(toggle === 'remove' ? 'sample_removed' : 'sample_added', button, payload.data);
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
        this.syncHeaderCount(state.cartCount);
    }

    /**
     * Keep the header basket badge in step. It is server-rendered on page load,
     * and is omitted entirely when the basket is empty, so it may need creating.
     *
     * @param {number} cartCount Total items in the basket.
     */
    syncHeaderCount(cartCount) {
        if (typeof cartCount !== 'number') return;

        const link = document.querySelector('.site-header__basket-link');
        if (!link) return;

        let badge = link.querySelector('.site-header__basket-count');

        if (!cartCount) {
            if (badge) badge.remove();
            return;
        }

        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'site-header__basket-count';
            link.appendChild(badge);
        }

        badge.textContent = String(cartCount);
    }

    /**
     * Show a short notice pinned to the button the visitor just pressed.
     *
     * Fixed positioning against the button's own rect, so it cannot be clipped
     * by the card's overflow. Dismissed on the next scroll, resize, outside
     * click, Escape, or after a few seconds.
     *
     * @param {HTMLElement} button The button that was refused.
     * @param {string} message Text to show.
     */
    showLimitNotice(button, message) {
        this.hideLimitNotice();

        const notice = document.createElement('div');
        notice.className = 'product-samples__limit';
        notice.setAttribute('role', 'alert');
        notice.textContent = message;
        document.body.appendChild(notice);

        this.limitNotice = notice;
        this.limitAnchor = button;
        this.positionLimitNotice();

        // Announce it to the button for assistive tech while it is on screen.
        button.setAttribute('aria-describedby', 'product-samples-limit');
        notice.id = 'product-samples-limit';

        this.limitDismiss = (event) => {
            if (event && event.type === 'click' && notice.contains(event.target)) return;
            if (event && event.type === 'keydown' && event.key !== 'Escape') return;
            this.hideLimitNotice();
        };

        window.addEventListener('scroll', this.limitDismiss, { passive: true, once: true });
        window.addEventListener('resize', this.limitDismiss, { once: true });
        document.addEventListener('keydown', this.limitDismiss);
        // Defer the click listener so this same click doesn't close it instantly.
        window.setTimeout(() => document.addEventListener('click', this.limitDismiss), 0);

        window.clearTimeout(this.limitTimer);
        this.limitTimer = window.setTimeout(() => this.hideLimitNotice(), 5000);
    }

    positionLimitNotice() {
        if (!this.limitNotice || !this.limitAnchor) return;

        const rect = this.limitAnchor.getBoundingClientRect();
        const notice = this.limitNotice;
        const width = notice.offsetWidth;
        const height = notice.offsetHeight;

        // Prefer above the button; drop below when there isn't room.
        const above = rect.top - height - 8;
        const top = above > 8 ? above : rect.bottom + 8;

        let left = rect.left + rect.width / 2 - width / 2;
        left = Math.max(8, Math.min(left, window.innerWidth - width - 8));

        notice.style.top = `${Math.round(top)}px`;
        notice.style.left = `${Math.round(left)}px`;
        notice.classList.toggle('product-samples__limit--below', above <= 8);
    }

    hideLimitNotice() {
        window.clearTimeout(this.limitTimer);

        if (this.limitDismiss) {
            window.removeEventListener('scroll', this.limitDismiss);
            window.removeEventListener('resize', this.limitDismiss);
            document.removeEventListener('keydown', this.limitDismiss);
            document.removeEventListener('click', this.limitDismiss);
            this.limitDismiss = null;
        }

        if (this.limitAnchor) {
            this.limitAnchor.removeAttribute('aria-describedby');
            this.limitAnchor = null;
        }

        if (this.limitNotice) {
            this.limitNotice.remove();
            this.limitNotice = null;
        }
    }

    /**
     * @param {HTMLElement} button Button to render as already chosen.
     * @param {number} position Place in the basket.
     */
    renderInCart(button, position) {
        button.classList.add('product-samples__button--in-cart');
        button.dataset.sampleAction = 'remove';

        // The remove label carries a <strong> around the position, matching the
        // server-rendered markup. Only the position is interpolated, and it is
        // coerced to an integer first, so there is nothing to inject.
        const removeLabel = this.config.i18n.remove.replace('{position}', String(parseInt(position, 10) || 0));

        button.innerHTML =
            '<span>' +
            `<span class="product-samples__button__content product-samples__button__content--added">${this.escape(
                this.config.i18n.added,
            )}</span> ` +
            `<span class="product-samples__button__action">${removeLabel}</span>` +
            '</span>';
    }

    /**
     * @param {HTMLElement} button Button to restore to its addable state.
     */
    renderAddable(button) {
        button.classList.remove('product-samples__button--in-cart');
        button.dataset.sampleAction = 'add';

        // Rebuilt from the label parts PHP puts on the element, so this works for
        // buttons that were already in the basket when the page loaded and never
        // had an addable state to remember.
        button.innerHTML =
            '<span>' +
            `<span class="product-samples__button__content">${this.escape(button.dataset.sampleLabel)}</span> ` +
            `<span class="product-samples__button__dimensions">${this.escape(button.dataset.sampleDimensions)}</span> ` +
            `<span class="product-samples__button__price">${this.escape(button.dataset.samplePrice)}</span>` +
            '</span>';
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
        const target = this.bar.querySelector('.product-samples__bar__count');

        if (!count) {
            // Clear the text as well as hiding, so a stale count can't flash the
            // next time the bar is shown.
            target.textContent = '';
            this.bar.setAttribute('hidden', 'hidden');
            return;
        }

        this.bar.removeAttribute('hidden');
        target.textContent = this.config.i18n.chosen.replace('{count}', count);
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
     * Report a sample basket action to the data layer.
     *
     * An AJAX add produces no page view, so the ?add-to-cart= URL that every
     * previous sample measurement was built on disappears the moment this
     * feature is switched on for a locale. This is the replacement signal.
     * It is deliberately NOT called add_to_basket: that event already exists,
     * fires only for sessions that landed on one particular page, and cannot
     * be compared across landing pages.
     *
     * @param {string} event Event name.
     * @param {HTMLElement} button Button that was pressed.
     * @param {object|null} state Basket state returned by the server.
     */
    track(event, button, state) {
        // Never let an analytics failure break the basket.
        try {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event,
                sample_product_id: Number(button.dataset.sampleProductId) || null,
                sample_name: button.dataset.sampleName || null,
                // The server is the source of truth for the count, so a
                // refusal reports the real basket rather than an optimistic one.
                sample_basket_count: state && typeof state.count === 'number' ? state.count : null,
            });
        } catch (error) {
            // Swallowed on purpose.
        }
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
