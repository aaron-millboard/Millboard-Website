/**
 * Advice category slider.
 *
 * The rail itself is native scrolling with scroll-snap. This adds the two
 * things the design draws on top of it: previous and next buttons that move
 * one card at a time, and a progress bar that shows how much of the rail is in
 * view and where.
 *
 * Both stay hidden until there is something to scroll. The card count is
 * editorial, and three cards fit a wide screen, so whether the rail overflows
 * cannot be a media query.
 */
export default class AdviceCategorySlider {
    constructor(element) {
        this.el = element;
        this.track = this.el.querySelector('.advice-category-slider__track');
        this.buttons = this.el.querySelector('.advice-category-slider__buttons');
        this.prev = this.el.querySelector('[data-advice-rail="prev"]');
        this.next = this.el.querySelector('[data-advice-rail="next"]');
        this.progress = this.el.querySelector('.advice-category-slider__progress');
        this.bar = this.el.querySelector('.advice-category-slider__progress-bar');

        if (!this.track) {
            return;
        }

        this.update = this.update.bind(this);
        this.frame = null;

        this.prev?.addEventListener('click', () => this.step(-1));
        this.next?.addEventListener('click', () => this.step(1));

        this.track.addEventListener('scroll', () => this.schedule(), { passive: true });
        window.addEventListener('resize', () => this.schedule());

        // Fonts and images change the track's width after first paint.
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(this.update).catch(() => {});
        }

        this.track.querySelectorAll('img').forEach((image) => {
            if (!image.complete) {
                image.addEventListener('load', () => this.schedule(), { once: true });
            }
        });

        this.update();
    }

    schedule() {
        if (this.frame) {
            return;
        }

        this.frame = window.requestAnimationFrame(() => {
            this.frame = null;
            this.update();
        });
    }

    /**
     * Move by one card. Measured from the cards themselves rather than a fixed
     * distance, because the card width is fluid.
     */
    step(direction) {
        const items = this.track.children;

        if (!items.length) {
            return;
        }

        const gap = parseFloat(window.getComputedStyle(this.track).columnGap) || 0;
        const distance = items[0].getBoundingClientRect().width + gap;

        this.track.scrollBy({ left: direction * distance, behavior: this.motion() });
    }

    motion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
    }

    update() {
        const max = this.track.scrollWidth - this.track.clientWidth;

        // A pixel of slack: sub-pixel widths otherwise report an overflow that
        // no one can actually scroll.
        const scrollable = max > 1;

        if (this.buttons) {
            this.buttons.hidden = !scrollable;
        }

        if (this.progress) {
            this.progress.hidden = !scrollable;
        }

        if (!scrollable) {
            return;
        }

        const position = Math.min(1, Math.max(0, this.track.scrollLeft / max));
        const visible = Math.min(1, this.track.clientWidth / this.track.scrollWidth);

        if (this.prev) {
            this.prev.disabled = this.track.scrollLeft <= 1;
        }

        if (this.next) {
            this.next.disabled = this.track.scrollLeft >= max - 1;
        }

        if (this.bar) {
            const width = Math.max(0.12, visible);
            this.bar.style.width = `${width * 100}%`;
            this.bar.style.transform = `translateX(${position * ((1 - width) / width) * 100}%)`;
        }
    }
}
