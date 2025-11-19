import debounce from 'lodash.debounce';
import Focusable from '../../../scripts/helpers/Focusable.js';

/**
 * Slider component with accessibility features and multiple items in view support.
 * Similar DOM manipulation patterns to StackerHorizontal but optimized for horizontal sliding.
 */
export default class Slider {
    /**
     * @param {HTMLElement} element The slider container element.
     * @param {Object} [args]
     * @param {number} [args.itemsInView=1] Number of items visible at once.
     * @param {boolean} [args.enableKeyboard=true] Enable keyboard navigation.
     * @param {boolean} [args.enableTouch=true] Enable touch/swipe gestures.
     * @param {boolean} [args.respectReducedMotion=true] Respect prefers-reduced-motion.
     * @param {string} [args.transitionDuration="300ms"] CSS transition duration.
     * @param {number} [args.debounceDelay=16] Debounce delay for resize events.
     */
    constructor(element, args = {}) {
        this.wrapper = element;
        this.element = this.wrapper.querySelector('.slider');
        this.args = {
            itemsInView: 1,
            enableKeyboard: true,
            enableTouch: true,
            respectReducedMotion: true,
            transitionDuration: '300ms',
            debounceDelay: 16,
            ...args,
        };

        // DOM Elements
        this.slides = Array.from(this.element.querySelectorAll('.slider__track > li'));
        this.track = this.element.querySelector('.slider__track') || this.element;
        this.prevBtn = this.element.querySelector('.slider__navigation--previous');
        this.nextBtn = this.element.querySelector('.slider__navigation--next');
        this.pips = Array.from(this.element.querySelectorAll('.slider__pip'));
        this.screenReaderLiveRegion = this.element.querySelector('.slider__screen-reader-live-region');
        this.counter = this.element.querySelector('.slider__counter');

        // Get config.
        const elStyles = window.getComputedStyle(this.element);
        this.args.itemsInView = Number(elStyles.getPropertyValue('--slider-items-in-view'));
        this.args.transitionDuration = elStyles.getPropertyValue('--slider-transition-duration');
        this.args.totalSlides = Number(elStyles.getPropertyValue('--slider-total-slides'));

        // State
        this.currentIndex = 0;
        this.totalSlides = this.slides.length;
        this.isTransitioning = false;
        this.touchStartX = 0;
        this.touchEndX = 0;
        this.minSwipeDistance = 50;
        this.focusableItemsMap = {};

        // Bind handlers
        this.onResize = this.onResize.bind(this);
        this.onKeydown = this.onKeydown.bind(this);
        this.onTouchStart = this.onTouchStart.bind(this);
        this.onTouchMove = this.onTouchMove.bind(this);
        this.onTouchEnd = this.onTouchEnd.bind(this);

        // Create debounced resize listener
        this.debouncedResizeListener = debounce(this.onResize, this.args.debounceDelay);

        // Bail early if no slides
        if (!this.totalSlides) return;

        // Initialize component
        this.init();
    }

    /**
     * Initialize the component
     */
    init() {
        this.handleReducedMotion();

        this.updateTrackTransform();

        this.initNavigation();

        this.initAccessibility();

        this.render();

        // Event listeners
        window.addEventListener('resize', this.debouncedResizeListener, { passive: true });

        if (this.args.enableKeyboard) {
            this.element.addEventListener('keydown', this.onKeydown);
        }

        if (this.args.enableTouch) {
            this.setupTouchEvents();
        }
    }

    /**
     * Initialize slide attributes for accessibility and styling
     */
    initializeSlideAttributes() {
        const elementId = this.element.id || this.element.getAttribute('data-ref') || 'slider';

        this.slides.forEach((slide, index) => {
            // Add slider-specific class
            slide.classList.add('slider__slide');

            // Set unique ID for each slide
            const slideId = `${elementId}-slide-${index + 1}`;
            slide.id = slideId;

            // Set aria attributes
            slide.setAttribute('role', 'tabpanel');
            slide.setAttribute('aria-roledescription', 'slide');
            slide.setAttribute('aria-label', `Slide ${index + 1} of ${this.totalSlides}`);

            // Update corresponding dot aria-controls if it exists
            if (this.pips[index]) {
                this.pips[index].setAttribute('aria-controls', slideId);
            }
        });
    }

    /**
     * Initialize navigation controls
     */
    initNavigation() {
        // Previous button
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => this.previous());
            this.prevBtn.setAttribute('aria-label', 'Previous slide');
            this.prevBtn.setAttribute('aria-controls', this.getVisibleSlideIds().join(' '));
        }

        // Next button
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.next());
            this.nextBtn.setAttribute('aria-label', 'Next slide');
            this.nextBtn.setAttribute('aria-controls', this.getVisibleSlideIds().join(' '));
        }

        // Dots navigation
        if (this.pips.length > 0) {
            this.pips.forEach((dot, index) => {
                dot.addEventListener('click', () => this.goTo(index));
                dot.setAttribute('role', 'tab');
                dot.setAttribute('aria-label', `Go to slide ${index + 1}`);
            });
        }
    }

    /**
     * Initialize accessibility features
     */
    initAccessibility() {
        // Set up live region for screen reader announcements
        this.setupScreenReaderLiveRegion();

        // Set up focus management
        this.setupFocusManagement();

        // Set initial ARIA states
        this.updateAriaStates();
    }

    /**
     * Set up screen reader live region for announcements
     */
    setupScreenReaderLiveRegion() {
        // Check if live region already exists
        if (!this.screenReaderLiveRegion) {
            this.screenReaderLiveRegion = document.createElement('div');
            this.screenReaderLiveRegion.className = 'slider__screen-reader-live-region visually-hidden';
            this.screenReaderLiveRegion.setAttribute('aria-live', 'polite');
            this.screenReaderLiveRegion.setAttribute('aria-atomic', 'true');
            this.element.appendChild(this.screenReaderLiveRegion);
        }
    }

    /**
     * Setup focus management for keyboard navigation
     */
    setupFocusManagement() {
        // Add focus event listeners to all focusable elements within slides
        this.slides.forEach((slide, slideIndex) => {
            const slideId = slide.id;
            const focusable = new Focusable(slide);
            const focusableItems = focusable.all;
            this.focusableItemsMap[slideId] = focusableItems;

            focusableItems.forEach((focusableElement) => {
                focusableElement.addEventListener('focus', () => {
                    // Only scroll to slide if it's not currently visible
                    const isSlideVisible =
                        slideIndex >= this.currentIndex && slideIndex < this.currentIndex + this.args.itemsInView;

                    if (!isSlideVisible && !this.isTransitioning) {
                        this.goTo(slideIndex);
                    }
                });
            });
        });
    }

    /**
     * Get IDs of currently visible slides
     * @returns {string[]} Array of slide IDs
     */
    getVisibleSlideIds() {
        const visibleIds = [];
        /* eslint-disable-next-line no-plusplus */
        for (let i = this.currentIndex; i < this.currentIndex + this.args.itemsInView && i < this.totalSlides; i++) {
            if (this.slides[i] && this.slides[i].id) {
                visibleIds.push(this.slides[i].id);
            }
        }
        return visibleIds;
    }

    /**
     * Update the track transform based on current index
     */
    updateTrackTransform() {
        const translateX = -(this.currentIndex * (100 / this.totalSlides));
        this.track.style.setProperty('--slider-translate-x', `${translateX}%`);
    }

    /**
     * Render the slider state
     */
    render() {
        this.updateTrackTransform();
        this.updateNavigation();
        this.updateAriaStates();
        this.updateScreenReaderAnnouncement();
        this.updateCounter();
    }

    /**
     * Update navigation button states
     */
    updateNavigation() {
        const visibleSlideIds = this.getVisibleSlideIds();

        // Update previous button
        if (this.prevBtn) {
            const isDisabled = this.currentIndex === 0;
            this.prevBtn.disabled = isDisabled;
            this.prevBtn.setAttribute('aria-disabled', isDisabled);
            this.prevBtn.setAttribute('aria-controls', visibleSlideIds.join(' '));
        }

        // Update next button
        if (this.nextBtn) {
            const isDisabled = this.currentIndex >= this.totalSlides - this.args.itemsInView;
            this.nextBtn.disabled = isDisabled;
            this.nextBtn.setAttribute('aria-disabled', isDisabled);
            this.nextBtn.setAttribute('aria-controls', visibleSlideIds.join(' '));
        }

        // Update dots
        if (this.pips.length > 0) {
            this.pips.forEach((dot, index) => {
                const isActive = index === this.currentIndex;
                dot.setAttribute('aria-selected', isActive);
                dot.classList.toggle('is-active', isActive);
            });
        }
    }

    /**
     * Update ARIA states for accessibility.
     */
    updateAriaStates() {
        // Update slides
        this.slides.forEach((slide, index) => {
            const isVisible = index >= this.currentIndex && index < this.currentIndex + this.args.itemsInView;

            slide.setAttribute('aria-hidden', !isVisible);
            slide.setAttribute('tabindex', isVisible ? '0' : '-1');

            // Update aria-selected for tabpanel role
            slide.setAttribute('aria-selected', isVisible ? 'true' : 'false');
        });

        // Update track with current state
        this.track.setAttribute('aria-live', 'polite');
        this.track.setAttribute('aria-label', `Slider showing slide ${this.currentIndex + 1} of ${this.totalSlides}`);
    }

    /**
     * Update screen reader announcement
     */
    updateScreenReaderAnnouncement() {
        if (this.screenReaderLiveRegion instanceof HTMLElement) {
            const slideTitle = this.getSlideTitle(this.currentIndex);
            let announcement = `Slide ${this.currentIndex + 1} of ${this.totalSlides}`;

            if (slideTitle) {
                announcement += `: ${slideTitle}`;
            }

            // Add information about visible slides if multiple items in view
            if (this.args.itemsInView > 1) {
                const visibleCount = Math.min(this.args.itemsInView, this.totalSlides - this.currentIndex);
                announcement += `. Showing ${visibleCount} slide${visibleCount > 1 ? 's' : ''}`;
            }

            this.screenReaderLiveRegion.textContent = announcement;
        }
    }

    /**
     * Get the title for a slide
     * @param {number} index The slide index
     * @returns {string} The slide title
     */
    getSlideTitle(index) {
        const slide = this.slides[index];
        return slide?.dataset.title || slide?.querySelector('h1, h2, h3, h4, h5, h6')?.textContent?.trim() || '';
    }

    /**
     * Update counter display
     */
    updateCounter() {
        if (this.counter) {
            const current = this.currentIndex + 1;
            const total = this.totalSlides;
            this.counter.textContent = `${current} / ${total}`;
        }
    }

    /**
     * Go to a specific slide
     * @param {number} index The slide index to go to
     * @param {boolean} [smooth=true] Whether to use smooth transition
     */
    goTo(index, smooth = true) {
        // Bail early if already transitioning.
        if (this.isTransitioning) return;

        const targetIndex = this.clampValue(index, 0, this.totalSlides - 1);

        // Bail early if already on the target index.
        if (targetIndex === this.currentIndex) return;

        // Set transitioning state.
        this.isTransitioning = true;
        this.currentIndex = targetIndex;

        // Add transition class for smooth animation
        if (smooth) {
            this.track.setAttribute('data-track-transitioning', 'true');
        }

        this.render();

        // Remove transition class after animation completes
        if (smooth) {
            setTimeout(() => {
                this.track.setAttribute('data-track-transitioning', 'false');
                this.isTransitioning = false;
            }, parseInt(this.args.transitionDuration, 10));
        } else {
            this.isTransitioning = false;
        }
    }

    /**
     * Go to the next slide
     */
    next() {
        let nextIndex = this.currentIndex + 1;

        if (nextIndex >= this.totalSlides) {
            nextIndex = this.totalSlides - 1;
        }

        this.goTo(nextIndex);
    }

    /**
     * Go to the previous slide
     */
    previous() {
        let prevIndex = this.currentIndex - 1;

        if (prevIndex < 0) {
            prevIndex = 0;
        }

        this.goTo(prevIndex);
    }

    /**
     * Setup touch events for swipe gestures
     */
    setupTouchEvents() {
        // this.element.addEventListener('touchstart', this.onTouchStart, { passive: true });
        // this.element.addEventListener('touchmove', this.onTouchMove, { passive: true });
        // this.element.addEventListener('touchend', this.onTouchEnd, { passive: true });

        this.slides.forEach(slide => {
            slide.addEventListener('touchstart', this.onTouchStart, { passive: true });
            slide.addEventListener('touchmove', this.onTouchMove, { passive: true });
            slide.addEventListener('touchend', this.onTouchEnd, { passive: true });
        });
    }

    /**
     * Handle touch start
     * @param {TouchEvent} event
     */
    onTouchStart(event) {
        this.touchStartX = event.touches[0].clientX;
    }

    /**
     * Handle touch move
     * @param {TouchEvent} event
     */
    onTouchMove(event) {
        this.touchEndX = event.touches[0].clientX;
    }

    /**
     * Handle touch end
     * @param {TouchEvent} event
     */
    /* eslint-disable-next-line no-unused-vars */
    onTouchEnd(event) {
        if (!this.touchStartX || !this.touchEndX) return;

        const distance = this.touchStartX - this.touchEndX;
        const isLeftSwipe = distance > this.minSwipeDistance;
        const isRightSwipe = distance < -this.minSwipeDistance;

        if (isLeftSwipe) {
            this.next();
        } else if (isRightSwipe) {
            this.previous();
        }

        this.touchStartX = 0;
        this.touchEndX = 0;
    }

    /**
     * Handle keyboard navigation
     * @param {KeyboardEvent} event
     */
    onKeydown(event) {
        const keyActions = {
            ArrowLeft: () => this.previous(),
            ArrowRight: () => this.next(),
            Home: () => this.goTo(0),
            End: () => this.goTo(this.totalSlides - 1),
            ' ': () => {
                event.preventDefault();
                this.next();
            },
            PageDown: () => this.next(),
            PageUp: () => this.previous(),
        };

        if (keyActions[event.key]) {
            event.preventDefault();
            keyActions[event.key]();
        }
    }

    /**
     * Handle resize
     */
    onResize() {
        // this.setupLayout();
        this.updateTrackTransform();

        this.render();
    }

    /**
     * Handle reduced motion preference
     */
    handleReducedMotion() {
        if (
            this.args.respectReducedMotion &&
            window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            this.element.setAttribute('data-reduced-motion', 'true');
            this.element.style.setProperty('--slider-transition-duration', '0ms');
        }
    }

    /**
     * Clamp a value between min and max
     * @param {number} value The value to clamp
     * @param {number} min The minimum value
     * @param {number} max The maximum value
     * @returns {number} The clamped value
     */
    /* eslint-disable-next-line class-methods-use-this */
    clampValue(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }
}
