import Player from '@vimeo/player';

export default class HeroHeader {
   /**
     * @param {HTMLElement} element The gallery container element.
     */
    constructor(element) {
        this.element = element;
        this.media = this.element.querySelector('.hero-header__media');

        if (!this.media) {
            return;
        }

        this.state = 'paused';
        this.controlButton = this.media.querySelector('.hero-header__controls');
        this.iframe = this.media.querySelector('.hero-header__iframe');

        if (Player && this.iframe) {
            this.player = new Player(this.iframe);

            this.player.ready().then(() => {
                this.init();
            });
        }
    }

    /**
     * Initialize the component
     */
    init() {
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.pauseVideo();
            }
        });

        // Hover-to-play only on true mouse devices (real hover + fine pointer).
        // Touch and hybrid devices (including iPads, which report
        // `hover: hover`) use the visible play button instead.
        if (matchMedia('(hover: hover) and (pointer: fine)').matches) {
            this.media.addEventListener('mouseenter', this);
            this.media.addEventListener('mouseleave', this);
        }

        // Always wire up the control button. It is only shown where hover is
        // unavailable (see block.scss), but attaching the handler
        // unconditionally means a tap works wherever the button is visible.
        // The previous visibility gate could skip it on some tablets, leaving
        // the button dead.
        if (this.controlButton) {
            this.controlButton.addEventListener('click', () => {
                if (this.state === 'paused') {
                    this.playVideo();
                } else {
                    this.pauseVideo();
                }
            });
        }

        if (this.media.matches(':hover')) {
            this.playVideo();
        }
    }

    /**
     * Play video
     */
    playVideo() {
        if (!this.player) {
            return;
        }

        this.player.play().then(() => {
            this.element.classList.add('hero-header--playing');
            this.setControlLabel('data-pause-label');
            this.state = 'playing';
        }).catch(() => {
            // Playback can be rejected by the browser (autoplay policy); keep
            // the paused state so the button can be tapped again.
            this.state = 'paused';
        });
    }

    /**
     * Stop all videos
     */
    pauseVideo() {
        if (!this.player) {
            return;
        }

        this.player.pause().then(() => {
            this.element.classList.remove('hero-header--playing');
            this.setControlLabel('data-play-label');
            this.state = 'paused';
        });
    }

    /**
     * Update the control button's visually-hidden label, guarding against a
     * missing child node.
     *
     * @param {string} attribute The data attribute holding the label text.
     */
    setControlLabel(attribute) {
        if (!this.controlButton) {
            return;
        }

        const label = this.controlButton.getAttribute(attribute);
        const target = this.controlButton.firstElementChild;

        if (target && label) {
            target.textContent = label;
        }
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
    }

    onmouseenter() {
        if (this.state !== 'playing') {
            this.playVideo();
        }
    }

    onmouseleave() {
        if (this.state === 'playing') {
            this.pauseVideo();
        }
    }
}
