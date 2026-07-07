import Player from '@vimeo/player';
import isElementVisible from '../../../scripts/helpers/isElementVisible.js';

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

        // Device has a mouse - set up mouse interactions.
        if (matchMedia('(hover:hover)').matches) {
            this.media.addEventListener('mouseenter', this);
            this.media.addEventListener('mouseleave', this);
        }

        if (this.controlButton && this.areControlsActive()) {
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
        if (this.player) {
            this.player.play().then(() => {
                this.element.classList.add('hero-header--playing');
                this.controlButton.firstElementChild.textContent = this.controlButton.getAttribute('data-pause-label');
                this.state = 'playing';
            }) ;
        }
    }

    /**
     * Stop all videos
     */
    pauseVideo() {
        if (this.player) {
            this.player.pause().then(() => {
                this.element.classList.remove('hero-header--playing');
                this.controlButton.firstElementChild.textContent = this.controlButton.getAttribute('data-play-label');
                this.state = 'paused';
            });
        }
    }

    areControlsActive() {
        return isElementVisible(this.controlButton);
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
