const FOCUSABLE = 'button, [href], iframe, [tabindex]:not([tabindex="-1"])';

/**
 * Right-hand drawer that hosts the visualiser embed for the current product.
 *
 * The entry points live elsewhere in the buy box and are found by the
 * data-visualiser-open attribute. The embed is keyed by the product's SKU.
 */
export default class ProductVisualiser {
    constructor(root) {
        this.root = root;
        this.drawer = root.querySelector('.product-visualiser__drawer');
        this.overlay = root.querySelector('.product-visualiser__overlay');
        this.body = root.querySelector('.product-visualiser__body');
        this.tab = root.querySelector('.product-visualiser__tab');
        this.embedUrl = root.dataset.embedUrl;
        this.origin = root.dataset.embedOrigin;
        this.sku = root.dataset.sku;
        this.loaded = false;
        this.trigger = null;

        document.querySelectorAll('[data-visualiser-open]').forEach((el) => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                this.open(el);
            });
        });

        root.querySelectorAll('[data-visualiser-close]').forEach((el) => el.addEventListener('click', () => this.close()));
        this.drawer.addEventListener('keydown', (e) => this.onKeydown(e));
        window.addEventListener('message', (e) => this.onMessage(e));
    }

    src() {
        const url = new URL(this.embedUrl);
        url.searchParams.set('sku', this.sku);

        return url.toString();
    }

    open(trigger) {
        this.trigger = trigger;

        // Created once, so reopening the drawer keeps a photo the visitor has
        // already uploaded.
        if (!this.loaded) {
            const frame = document.createElement('iframe');
            frame.className = 'product-visualiser__frame';
            frame.title = this.root.querySelector('.product-visualiser__title').textContent.trim();
            frame.src = this.src();
            frame.allow = 'camera';
            this.body.appendChild(frame);
            this.loaded = true;
        }

        this.overlay.hidden = false;
        this.drawer.hidden = false;
        document.body.classList.add('product-visualiser--open');
        if (this.tab) this.tab.hidden = true;
        this.drawer.querySelector('.product-visualiser__close').focus();

        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ event: 'visualiser_open', visualiser_sku: this.sku });
    }

    close() {
        this.overlay.hidden = true;
        this.drawer.hidden = true;
        document.body.classList.remove('product-visualiser--open');
        if (this.tab) this.tab.hidden = false;

        if (this.trigger && this.trigger.isConnected && this.trigger.offsetParent !== null) {
            this.trigger.focus();
        }
    }

    onKeydown(e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            this.close();
            return;
        }

        if (e.key !== 'Tab') return;

        const items = Array.from(this.drawer.querySelectorAll(FOCUSABLE));
        if (!items.length) return;

        const first = items[0];
        const last = items[items.length - 1];

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    /**
     * Key presses inside the iframe never reach this page, so the embed has to
     * ask to be closed. Only messages from the embed's own origin count.
     */
    onMessage(e) {
        if (e.origin !== this.origin || this.drawer.hidden) return;

        if (e.data && e.data.type === 'close') this.close();
    }
}
