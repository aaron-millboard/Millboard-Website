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
        this.controlButton = this.media.querySelector('.hero-header__controls');

        this.init();
    }

    /**
     * Initialize the component
     */
    init() {
        if (this.controlButton) {
            this.controlButton.addEventListener('click', () => {
                if (this.state === 'paused') {
                    this.playVideo();
                } else {
                    this.pauseVideo();
                }
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.pauseVideo();
            }
        });
    }

    /**
     * Play video
     */
    playVideo() {
        const embedUrl = this.iframe.getAttribute('data-embed-url');

        if (!embedUrl) {
            console.warn('No embed URL found for video');
            return;
        }


        this.iframe.src = embedUrl;
        this.element.classList.add('hero-header--playing');
        this.controlButton.firstElementChild.textContent = this.controlButton.getAttribute('data-pause-label');
        this.state = 'playing';
    }

    /**
     * Stop all videos
     */
    pauseVideo() {
        if (this.iframe) {
            this.iframe.src = '';
        }

        this.element.classList.remove('hero-header--playing');
        this.controlButton.firstElementChild.textContent = this.controlButton.getAttribute('data-play-label');
        this.state = 'paused';
    }
}
