/**
 * Background video for the page header.
 *
 * The video is decorative, so it stays out of the tab order and the image
 * behind it carries the meaning. Playback starts muted and loops, which
 * WCAG 2.2 SC 2.2.2 allows only if the visitor can stop it, so the pause
 * control is revealed as soon as we know the video can actually play. Anyone
 * who has asked for reduced motion gets the image and nothing else.
 *
 * Nothing here toggles a class that the stylesheet depends on. Perfmatters
 * Remove Unused CSS prunes rules whose selectors do not appear in the served
 * HTML, so state is carried by inline styles and the `hidden` attribute
 * instead, and both icons ship in the markup.
 */
export default class PageHeaderVideo {
    constructor(header) {
        this.header = header;
        this.video = header.querySelector('[data-page-header-video]');
        this.toggle = header.querySelector('[data-page-header-video-toggle]');

        if (!this.video) {
            return;
        }

        this.pauseIcon = header.querySelector('[data-page-header-video-icon="pause"]');
        this.playIcon = header.querySelector('[data-page-header-video-icon="play"]');
        this.label = header.querySelector('.page-header__video-toggle-label');

        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

        this.onToggleClick = this.onToggleClick.bind(this);
        this.onMotionPreferenceChange = this.onMotionPreferenceChange.bind(this);

        // Set as an attribute in the markup too, but Safari needs the property
        // itself set before play() for muted autoplay to be allowed.
        this.video.muted = true;

        if (this.toggle) {
            this.toggle.addEventListener('click', this.onToggleClick);
        }

        if (typeof this.reducedMotion.addEventListener === 'function') {
            this.reducedMotion.addEventListener('change', this.onMotionPreferenceChange);
        }

        if (this.reducedMotion.matches) {
            return;
        }

        this.start();
    }

    /**
     * Load and play the video, only revealing it and its pause control once
     * playback has actually been allowed to begin.
     */
    start() {
        this.video.preload = 'auto';
        this.video.load();

        const played = this.video.play();

        if (played === undefined) {
            this.reveal();
            return;
        }

        played.then(
            () => this.reveal(),
            // Autoplay was blocked. The image is already showing, so leave the
            // control hidden rather than offering a button that pauses
            // something the visitor cannot see.
            () => this.hide()
        );
    }

    /**
     * Fade the video in over the image and expose the pause control. The video
     * stays visible after a manual pause; a held frame is less jarring than
     * cutting back to the image.
     */
    reveal() {
        this.video.style.opacity = '1';

        if (this.toggle) {
            this.toggle.hidden = false;
        }

        this.setToggleState(true);
    }

    hide() {
        this.video.style.opacity = '';

        if (this.toggle) {
            this.toggle.hidden = true;
        }
    }

    onToggleClick() {
        if (this.video.paused) {
            this.video.play().then(
                () => this.setToggleState(true),
                () => this.setToggleState(false)
            );

            return;
        }

        this.video.pause();
        this.setToggleState(false);
    }

    /**
     * @param {boolean} isPlaying Whether the video is currently running.
     */
    setToggleState(isPlaying) {
        if (this.pauseIcon) {
            this.pauseIcon.hidden = !isPlaying;
        }

        if (this.playIcon) {
            this.playIcon.hidden = isPlaying;
        }

        if (!this.label || !this.toggle) {
            return;
        }

        const text = isPlaying
            ? this.toggle.dataset.labelPause
            : this.toggle.dataset.labelPlay;

        if (text) {
            this.label.textContent = text;
        }
    }

    /**
     * Respect a change of motion preference made after load.
     */
    onMotionPreferenceChange() {
        if (!this.reducedMotion.matches) {
            this.start();
            return;
        }

        this.video.pause();
        this.video.currentTime = 0;
        this.hide();
    }
}
