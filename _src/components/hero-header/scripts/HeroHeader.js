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
        // this.controlButton = this.media.querySelector('.hero-header__controls');
        this.videoReadyTimeout = null;

        this.init();
    }

    /**
     * Initialize the component
     */
    init() {
        const hasInitialVideo = this.iframe && this.iframe.getAttribute('src');

        if (hasInitialVideo) {
            this.element.classList.add('hero-header--playing');
            this.element.classList.add('hero-header--autoplaying');
            this.waitForIframeReady();

            // if (this.controlButton && this.controlButton.firstElementChild) {
            //     this.controlButton.firstElementChild.textContent = this.controlButton.getAttribute('data-pause-label');
            // }
            this.state = 'playing';
        }

        if (this.controlButton) {
            this.controlButton.addEventListener('click', () => {
                if (this.state === 'paused') {
                    this.playVideo(true);
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

        // Device has a mouse.
        if (matchMedia('(pointer:fine)').matches) {
            this.media.addEventListener('mouseenter', this);
            this.media.addEventListener('mouseleave', this);
        }
    }

    /**
     * Play video
     */
    playVideo(isManualPlay = false) {
        const embedUrl = this.iframe.getAttribute('data-embed-url');

        if (!embedUrl) {
            console.warn('No embed URL found for video');
            return;
        }

        this.waitForIframeReady();

        this.iframe.src = embedUrl;
        if (isManualPlay) {
            this.element.classList.remove('hero-header--autoplaying');
        }
        this.element.classList.add('hero-header--playing');
        // this.controlButton.firstElementChild.textContent = this.controlButton.getAttribute('data-pause-label');
        this.state = 'playing';
    }

    /**
     * Stop all videos
     */
    pauseVideo() {
        if (this.iframe) {
            this.iframe.src = '';
        }

        if (this.videoReadyTimeout) {
            clearTimeout(this.videoReadyTimeout);
            this.videoReadyTimeout = null;
        }

        this.element.classList.remove('hero-header--playing');
        this.element.classList.remove('hero-header--video-ready');
        // this.controlButton.firstElementChild.textContent = this.controlButton.getAttribute('data-play-label');
        this.state = 'paused';
    }

    /**
     * Wait for iframe to finish loading before revealing video.
     */
    waitForIframeReady() {
        this.element.classList.remove('hero-header--video-ready');

        if (!this.iframe) {
            return;
        }

        this.iframe.addEventListener('load', () => {
            this.element.classList.add('hero-header--video-ready');

            if (this.videoReadyTimeout) {
                clearTimeout(this.videoReadyTimeout);
                this.videoReadyTimeout = null;
            }
        }, { once: true });

        if (this.videoReadyTimeout) {
            clearTimeout(this.videoReadyTimeout);
        }

        this.videoReadyTimeout = setTimeout(() => {
            this.element.classList.add('hero-header--video-ready');
            this.videoReadyTimeout = null;
        }, 2000);
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
