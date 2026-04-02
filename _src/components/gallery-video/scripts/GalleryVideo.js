/**
 * Gallery Video component with thumbnail navigation and video switching.
 */
export default class GalleryVideo {
    /**
     * @param {HTMLElement} element The gallery container element.
     */
    constructor(element) {
        this.element = element;
        this.videos = Array.from(this.element.querySelectorAll('.gallery-video__video'));
        this.thumbnails = Array.from(this.element.querySelectorAll('.gallery-video__thumbnail'));
        this.metaPanels = Array.from(this.element.querySelectorAll('.gallery-video__meta--panel'));
        this.activeVideoIndex = 0;

        if (!this.videos.length) return;

        this.init();
    }

    /**
     * Initialize the component
     */
    init() {
        this.videos.forEach((video, index) => {
            const playButton = video.querySelector('.gallery-video__play-button');
            const metaPlayButton = video.querySelector('.gallery-video__meta__play');
            const iframe = video.querySelector('iframe');

            if (playButton) {
                playButton.addEventListener('click', () => {
                    this.playVideo(index, playButton, iframe);
                });
            }

            if (metaPlayButton) {
                metaPlayButton.addEventListener('click', () => {
                    this.playVideo(index, metaPlayButton, iframe);
                });
            }
        });

        this.thumbnails.forEach((thumbnail, index) => {
            thumbnail.addEventListener('click', () => {
                this.switchVideo(index);
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.stopAllVideos();
            }
        });
    }

    /**
     * Play video
     * @param {number} index Video index
     * @param {HTMLElement} button Play button element
     * @param {HTMLElement} iframe Iframe element
     */
    playVideo(index, button, iframe) {
        const embedUrl = button.getAttribute('data-embed-url');

        if (!embedUrl) {
            console.warn('No embed URL found for video');
            return;
        }

        iframe.src = embedUrl;
        this.videos[index].classList.add('gallery-video--playing');
    }

    /**
     * Stop all videos
     */
    stopAllVideos() {
        this.videos.forEach((video) => {
            const iframe = video.querySelector('iframe');
            if (iframe) {
                iframe.src = '';
            }
            video.classList.remove('gallery-video--playing');
        });
    }

    /**
     * Switch active video
     * @param {number} index Video index to switch to
     */
    switchVideo(index) {
        if (index === this.activeVideoIndex) return;

        this.stopAllVideos();

        this.videos.forEach((video, i) => {
            if (i === index) {
                video.classList.add('gallery-video__video--active');
            } else {
                video.classList.remove('gallery-video__video--active');
            }
        });

        this.thumbnails.forEach((thumbnail, i) => {
            if (i === index) {
                thumbnail.classList.add('gallery-video__thumbnail--active');
            } else {
                thumbnail.classList.remove('gallery-video__thumbnail--active');
            }
        });

        this.metaPanels.forEach((panel) => {
            const panelIndex = Number.parseInt(panel.dataset.videoIndex, 10);

            if (panelIndex === index) {
                panel.classList.add('gallery-video__meta--active');
            } else {
                panel.classList.remove('gallery-video__meta--active');
            }
        });

        this.activeVideoIndex = index;
    }
}
