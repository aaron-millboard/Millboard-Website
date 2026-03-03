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
        if (matchMedia('(pointer:fine)').matches) {
            this.media.addEventListener('mouseenter', this);
            this.media.addEventListener('mouseleave', this);
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
                this.state = 'paused';
            });
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
