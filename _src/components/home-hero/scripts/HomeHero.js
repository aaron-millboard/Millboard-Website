/**
 * Home hero.
 *
 * Writes the scroll progress and the pointer state as custom properties and does
 * nothing else -- every visual consequence lives in the stylesheet. One
 * rAF-throttled passive scroll listener, and no layout reads per frame at all.
 */
export default class HomeHero {
    constructor(element) {
        this.el = element;

        this.film = this.el.querySelector('[data-home-hero-film]');
        this.toggle = this.el.querySelector('[data-home-hero-toggle]');

        this.reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.raf = null;

        this.init();
    }

    init() {
        this.tick = this.tick.bind(this);
        this.onScroll = this.onScroll.bind(this);

        window.addEventListener('scroll', this.onScroll, { passive: true });
        window.addEventListener('resize', this.onScroll);


        if (this.film) {
            this.initFilm();
        }

        if (this.toggle) {
            this.initPointer();
        }

        this.tick();

        // The hero's offsetTop depends on the header's height, and the header
        // measures itself after its own script runs. Recompute once that has
        // settled rather than racing it, or --p starts out against a stale
        // offset.
        window.setTimeout(this.tick, 120);
    }

    /**
     * Scroll progress across the section, 0 to 1, plus its inverse.
     *
     * Set on the section rather than the document so more than one of these
     * could exist on a page without fighting over the same variable.
     */
    tick() {
        this.raf = null;

        if (this.reduced) {
            this.set('--p', '0');
            this.set('--op', '0');
            return;
        }

        const scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
        const runway = Math.max(1, this.el.offsetHeight - window.innerHeight);
        const progress = Math.min(1, Math.max(0, (scrollTop - this.el.offsetTop) / runway));

        this.set('--p', progress.toFixed(4));
        this.set('--op', (1 - progress).toFixed(4));
    }

    onScroll() {
        if (!this.raf) {
            this.raf = requestAnimationFrame(this.tick);
        }
    }

    /**
     * Is this a connection we should push 8.66MB down?
     *
     * Save-Data is an explicit request not to. 2g and slow-3g are not an
     * explicit request but the answer is the same, and on either the poster and
     * the page colour carry the hero perfectly well. The film is still one tap
     * away on the frame button.
     */
    shouldLoadFilm() {
        const connection = navigator.connection;

        if (!connection) {
            return true;
        }

        if (connection.saveData) {
            return false;
        }

        return !['slow-2g', '2g'].includes(connection.effectiveType);
    }

    /**
     * Hand the element its source. The markup ships without one so the browser
     * cannot start fetching the film while it still has a stylesheet, three
     * fonts and the scripts to get through.
     */
    loadFilm() {
        if (this.film.src) {
            return false;
        }

        const source = this.film.getAttribute('data-film-src');

        if (!source) {
            return false;
        }

        this.film.src = source;

        return true;
    }

    playFilm() {
        const started = this.film.play();

        if (started && started.catch) {
            started.catch(() => this.set('--home-hero--paused', '1'));
        }
    }

    initFilm() {
        this.film.muted = true;
        this.film.loop = true;
        this.film.playsInline = true;
        this.film.setAttribute('playsinline', '');

        // Mirror the element's real state rather than tracking our own: an
        // autoplay block, a tab switch or the OS pausing media would all leave
        // a private flag lying about which icon to show.
        this.film.addEventListener('play', () => this.set('--home-hero--paused', '0'));
        this.film.addEventListener('pause', () => this.set('--home-hero--paused', '1'));

        // Autoplay is a courtesy, not the point: with reduced motion asked for,
        // or on a connection that should not be spending this, the film waits
        // to be started deliberately.
        if (this.reduced || !this.shouldLoadFilm()) {
            this.set('--home-hero--paused', '1');
            return;
        }

        const begin = () => {
            this.loadFilm();

            if (this.film.readyState >= 2) {
                this.playFilm();
            } else {
                this.film.addEventListener('loadeddata', () => this.playFilm(), { once: true });
            }
        };

        // After load, so the film queues behind everything the page needs to
        // paint rather than alongside it.
        if (document.readyState === 'complete') {
            begin();
        } else {
            window.addEventListener('load', begin, { once: true });
        }
    }

    initPointer() {
        this.toggle.addEventListener('click', () => this.toggleFilm());

        this.toggle.addEventListener('pointermove', (event) => {
            // Touch reports a pointer for the duration of a tap; the ring is a
            // cursor stand-in and has no business appearing for one.
            if (event.pointerType === 'touch') {
                return;
            }

            const bounds = this.toggle.getBoundingClientRect();
            const x = event.clientX - bounds.left;
            const y = event.clientY - bounds.top;

            this.set('--home-hero--pointer-x', `${x.toFixed(1)}px`);
            this.set('--home-hero--pointer-y', `${y.toFixed(1)}px`);
            this.set('--home-hero--hover', '1');
        });

        this.toggle.addEventListener('pointerleave', () => this.clearPointer());
        this.toggle.addEventListener('blur', () => this.clearPointer());

        // Keyboard focus gets the ring too, so the control is visible to
        // someone who never moves a pointer over it.
        this.toggle.addEventListener('focus', () => this.set('--home-hero--hover', '1'));
    }

    clearPointer() {
        this.set('--home-hero--hover', '0');
    }

    toggleFilm() {
        if (!this.film) {
            return;
        }

        if (this.film.paused) {
            // Asking for it counts as opting in, so this is also how someone on
            // Save-Data or reduced motion gets the film if they want it.
            if (this.loadFilm()) {
                this.film.addEventListener('loadeddata', () => this.playFilm(), { once: true });
                return;
            }

            this.playFilm();

            return;
        }

        this.film.pause();
    }

    set(property, value) {
        this.el.style.setProperty(property, value);
    }
}
