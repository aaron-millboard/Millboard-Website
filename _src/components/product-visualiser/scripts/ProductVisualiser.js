const FOCUSABLE = 'button, [href], iframe, [tabindex]:not([tabindex="-1"])';

// Matches the drawer transition in styles/main.scss.
const TRANSITION_MS = 350;

/**
 * Right-hand drawer that hosts the visualiser embed for the current product.
 *
 * The entry points live elsewhere in the buy box and are found by the
 * data-visualiser-open attribute. The embed is keyed by the product's SKU.
 *
 * The embed talks back to this page with postMessage: {source:
 * 'millboard-visualiser', version: 1, type: ...}. Types used here are ready,
 * unavailable, funnel, navigate and close.
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
        this.closeTimer = null;
        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

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

    isOpen() {
        return this.root.classList.contains('product-visualiser--active');
    }

    open(trigger) {
        this.trigger = trigger;
        clearTimeout(this.closeTimer);

        // Created once, so reopening the drawer keeps a photo the visitor has
        // already uploaded.
        if (!this.loaded) {
            const frame = document.createElement('iframe');
            frame.className = 'product-visualiser__frame';
            frame.title = this.root.querySelector('.product-visualiser__title').textContent.trim();
            frame.src = this.src();
            frame.allow = 'camera';
            frame.addEventListener('load', () => this.body.classList.add('product-visualiser__body--loaded'));
            this.body.appendChild(frame);
            this.loaded = true;
        }

        // Unhide first, then add the class on the next frame, so the browser
        // has a starting position to slide in from.
        this.overlay.hidden = false;
        this.drawer.hidden = false;
        void this.drawer.offsetWidth;
        this.root.classList.add('product-visualiser--active');
        document.documentElement.classList.add('product-visualiser--open');
        this.drawer.querySelector('.product-visualiser__close').focus({ preventScroll: true });

        this.push({ event: 'visualiser_open', visualiser_sku: this.sku });
    }

    close() {
        if (!this.isOpen()) return;

        this.root.classList.remove('product-visualiser--active');
        document.documentElement.classList.remove('product-visualiser--open');

        // Hide once the slide-out has finished. With reduced motion there is no
        // slide, so hide straight away.
        const hide = () => {
            this.overlay.hidden = true;
            this.drawer.hidden = true;
        };

        if (this.reducedMotion.matches) {
            hide();
        } else {
            this.closeTimer = setTimeout(hide, TRANSITION_MS);
        }

        if (this.trigger && this.trigger.isConnected && this.trigger.offsetParent !== null) {
            this.trigger.focus({ preventScroll: true });
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

    push(data) {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push(data);
    }

    /**
     * Key presses inside the iframe never reach this page, so the embed asks for
     * things by message. Only the embed's own origin and message format count.
     */
    onMessage(e) {
        const data = e.data;

        if (e.origin !== this.origin || !data || data.source !== 'millboard-visualiser' || data.version !== 1) return;

        switch (data.type) {
            case 'close':
                this.close();
                break;

            case 'unavailable':
                this.markUnavailable();
                break;

            case 'funnel':
                this.push({ event: 'visualiser_funnel', visualiser_step: data.event, visualiser_sku: this.sku });
                break;

            case 'navigate':
                this.navigate(data.url);
                break;
        }
    }

    /**
     * The embed says it cannot run here (for example, not offered in the
     * visitor's country). Close the drawer, take the entry points away and let
     * the samples stand on their own.
     */
    markUnavailable() {
        this.close();

        document.querySelectorAll('[data-visualiser-open]').forEach((el) => {
            el.hidden = true;
        });
        this.root.classList.add('product-visualiser--unavailable');

        const group = document.querySelector('.product__try');
        if (!group) return;

        const intro = group.querySelector('.product__try-intro');
        if (intro && intro.dataset.fallback) intro.textContent = intro.dataset.fallback;

        if (!group.querySelector('.product-samples__button')) group.hidden = true;
    }

    /**
     * Only follows links on this site, because the message could carry any URL.
     */
    navigate(url) {
        try {
            const target = new URL(url, window.location.href);

            if (target.origin === window.location.origin) window.location.assign(target.href);
        } catch (err) {
            // Not a usable URL, so stay put.
        }
    }
}
