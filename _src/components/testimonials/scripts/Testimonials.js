import Slider from '../../slider/scripts/Slider.js';

export default class Testimonials extends Slider {
    /**
     * Extend the parent class and initialize the navigation.
     * This will now include the calculation of the position properties of the testimonials.
     */
    initNavigation() {
        super.initNavigation();

        this.navPositionHeight = 0;
        this.navPositionWidth = 0;

        // Loop through the slides and get the width and height of the content container.
        this.calculatePositionProperties();
    }

    /**
     * Render the testimonials.
     * This will now include the calculation of the position properties of the testimonials.
     */
    onResize() {
        super.onResize();

        // If the current index is greater than 0, update the CSS properties after the transition duration.
        if (this.currentIndex !== 0) {
            this.goTo(0);

            // Update CSS properties after the transition duration.
            setTimeout(() => {
                this.calculatePositionProperties();
            }, parseInt(this.args.transitionDuration, 10) * 1);
        } else {
            // Update CSS properties immediately.
            this.calculatePositionProperties();
        }
    }

    /**
     * Calculate the position properties of the testimonials.
     */
    calculatePositionProperties() {
        // Loop through the slides and get the width and height of the content container.
        this.slides.forEach((slide, index) => {
            const currentSlideContentContainer = slide.querySelector('.testimonial-item__content');

            if (!currentSlideContentContainer) {
                return;
            }

            // Set the offset left of the navigation position.
            if (index === 0) {
                this.navPositionWidth = currentSlideContentContainer.getBoundingClientRect().left;
                this.element.style.setProperty('--testimonials--content-container--offset-left', `${this.navPositionWidth}px`);
            }

            // Get the width and height of the content container.
            const { offsetHeight } = currentSlideContentContainer;

            // Update the width and height of the navigation position.
            if (offsetHeight > this.navPositionHeight) {
                this.navPositionHeight = offsetHeight;
            }

            // Update the CSS custom properties.
            this.element.style.setProperty('--testimonials-content-container-height', `${this.navPositionHeight}px`);
        });
    }
}
