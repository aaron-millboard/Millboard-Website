export default class Gallery {
    /**
     * Class to handle the gallery lightbox.
     */
    constructor(element) {
        this.element = element;

        // Config
        this.inertSelector = '.page-wrapper';
        this.preloadSpan = 2; // 0 will disable preloading.

        // Selectors (constants)
        this.selectors = {
            lightbox: '.gallery__lightbox',
            mainImage: '.gallery__lightbox__main-image__inner > *',
            captionMain: '.gallery__lightbox__main-image__caption__main',
            captionSecondary: '.gallery__lightbox__main-image__caption__secondary',
            counter: '.gallery__lightbox__counter',
            counterCurrent: '.gallery__lightbox__counter__current',
            announcement: '.gallery__lightbox__announcement',
            closeBtn: '.gallery__lightbox__close',
            thumbnailsPrevious: '.gallery__lightbox__control--previous',
            thumbnailsNext: '.gallery__lightbox__control--next',
            thumbnailsList: '.gallery__lightbox__thumbnails__list',
            cardButtons: '.gallery__card button',
            thumbnailButtons: '.gallery__lightbox__thumbnail button',
        };

        // State
        this.openIndex = 0;
        this.lastOpener = null;
        this.isOpen = false;
        this.preloadSeen = new Set();
        this.isMobile = window.matchMedia('(max-width: 768px)').matches;

        // Cache DOM elements
        this.cacheElements();

        // Build items from gallery triggers, keyed by lightboxIndex
        this.itemsByLightboxIndex = {};
        this.triggerCardButtons.forEach((cardButton) => {
            const { lightboxIndex } = cardButton.dataset;
            this.itemsByLightboxIndex[lightboxIndex] = this.getItemData(cardButton);
        });
        this.totalItems = Object.keys(this.itemsByLightboxIndex).length;

        // Bind event handlers
        this.focusTrapHandler = this.handleFocusTrap.bind(this);
        this.keyHandler = this.handleGlobalKeys.bind(this);
        this.thumbnailKeyHandler = this.handleThumbnailKeys.bind(this);

        // Initialize
        this.init();
    }

    // ====================================
    // Lifecycle Methods
    // ====================================

    /**
     * Cache all DOM elements.
     */
    cacheElements() {
        this.lightbox = this.element.querySelector(this.selectors.lightbox);
        this.mainImage = this.element.querySelector(this.selectors.mainImage);
        this.selectedCaptionMain = this.element.querySelector(this.selectors.captionMain);
        this.selectedCaptionSecondary = this.element.querySelector(this.selectors.captionSecondary);
        this.countEl = this.element.querySelector(this.selectors.counter);
        this.currentCountEl = this.element.querySelector(this.selectors.counterCurrent);
        this.announcementEl = this.element.querySelector(this.selectors.announcement);
        this.closeBtn = this.element.querySelector(this.selectors.closeBtn);
        this.thumbnailsPrevious = this.element.querySelector(this.selectors.thumbnailsPrevious);
        this.thumbnailsNext = this.element.querySelector(this.selectors.thumbnailsNext);
        this.thumbnailsList = this.element.querySelector(this.selectors.thumbnailsList);
        this.triggerCardButtons = Array.from(this.element.querySelectorAll(this.selectors.cardButtons));
        this.thumbnailButtons = Array.from(this.element.querySelectorAll(this.selectors.thumbnailButtons));
    }

    /**
     * Initialize the gallery.
     */
    init() {
        // Close button
        this.closeBtn?.addEventListener('click', () => this.closeLightbox());

        // Thumbnail navigation buttons
        this.thumbnailsPrevious?.addEventListener('click', () => this.scrollThumbs(-1));
        this.thumbnailsNext?.addEventListener('click', () => this.scrollThumbs(1));

        // Scroll events on thumbnails
        this.thumbnailsList?.addEventListener('scroll', () => this.updateThumbnailsNavigationState());

        // Keyboard navigation for thumbnails
        this.lightbox?.addEventListener('keydown', this.thumbnailKeyHandler);

        // Gallery card triggers
        this.triggerCardButtons.forEach((button) => {
            const { lightboxIndex } = button.dataset;
            this.addButtonListeners(button, lightboxIndex, () => this.openLightbox(lightboxIndex, button));
        });

        // Thumbnail buttons
        this.thumbnailButtons.forEach((button) => {
            const { lightboxIndex } = button.dataset;
            this.addButtonListeners(button, lightboxIndex, () => this.goTo(lightboxIndex, button));
        });
    }

    /**
     * Add click and keyboard listeners to a button.
     *
     * @param {HTMLButtonElement} button The button element.
     * @param {number} index The index.
     * @param {Function} callback The callback function.
     */
    /* eslint-disable-next-line class-methods-use-this */
    addButtonListeners(button, index, callback) {
        button.addEventListener('click', callback);
        button.addEventListener('keydown', (e) => {
            if ((e.key === 'Enter' || e.key === ' ') && !e.repeat) {
                e.preventDefault();
                callback();
            }
        });
    }

    // ====================================
    // Lightbox Control
    // ====================================

    /**
     * Open the lightbox.
     *
     * @param {number} index The index of the item to open.
     * @param {HTMLElement} openerEl The element that opened the lightbox.
     */
    openLightbox(index = 0, openerEl = null) {
        if (this.isOpen) return;

        // Set state
        this.isOpen = true;
        this.openIndex = this.clamp(index);

        this.lastOpener = openerEl || this.findCardButtonByLightboxIndex(this.openIndex);

        // Update UI
        this.lockBodyScroll();
        this.showLightbox();
        this.setInertiaOnAllOtherElements(true);
        this.updateMainImage(this.openIndex);
        this.updateThumbnailsNavigationState();

        // Focus management
        (this.closeBtn || this.lightbox)?.focus({ preventScroll: true });

        // Add event listeners
        document.addEventListener('keydown', this.keyHandler);
        this.lightbox?.addEventListener('keydown', this.focusTrapHandler, true);
    }

    /**
     * Close the lightbox.
     */
    closeLightbox() {
        if (!this.isOpen) return;

        // Set state
        this.isOpen = false;

        // Remove event listeners
        document.removeEventListener('keydown', this.keyHandler);
        this.lightbox?.removeEventListener('keydown', this.focusTrapHandler, true);

        // Update UI
        this.hideLightbox();
        this.setInertiaOnAllOtherElements(false);
        this.lockBodyScroll();

        // Restore focus
        if (this.lastOpener && document.contains(this.lastOpener)) {
            this.lastOpener.focus({ preventScroll: true });
        }
    }

    /**
     * Show the lightbox.
     */
    showLightbox() {
        this.lightbox?.removeAttribute('hidden');
        this.lightbox?.removeAttribute('aria-hidden');
        this.element.setAttribute('data-open', true);
    }

    /**
     * Hide the lightbox.
     */
    hideLightbox() {
        this.lightbox?.setAttribute('hidden', 'hidden');
        this.lightbox?.setAttribute('aria-hidden', 'true');
        this.element.removeAttribute('data-open');
    }

    // ====================================
    // Navigation Methods
    // ====================================

    /**
     * Go to the next item.
     */
    next() {
        const index = this.nextIndex(this.openIndex);
        if (index === this.openIndex) return;
        this.goTo(index);
    }

    /**
     * Go to the previous item.
     */
    prev() {
        const index = this.prevIndex(this.openIndex);
        if (index === this.openIndex) return;
        this.goTo(index);
    }

    /**
     * Go to the item at the given index.
     *
     * @param {number} index The index of the item to go to.
     * @param {Object} options Trhe options.
     */
    goTo(index, { focusThumb = false } = {}) {
        const i = this.clamp(index);
        this.openIndex = i;
        this.updateMainImage(i, { focusThumb });
    }

    // ====================================
    // UI Update Methods
    // ====================================

    /**
     * Update the main image and related UI.
     *
     * @param {number} index The index of the item to update.
     * @param {Object} options The options.
     */
    updateMainImage(index, { focusThumb = false } = {}) {
        const itemToRender = this.itemsByLightboxIndex[index];

        if (!itemToRender) return;

        this.setLoadingState(true);
        this.updateImage(itemToRender);
        this.updateCaptions(itemToRender);
        this.updateCounter(index, itemToRender);
        this.updateThumbnailStates(index);
        this.scrollToActiveThumbnail(index, focusThumb);
        this.preloadNeighbors(index);
    }

    /**
     * Set the loading state.
     *
     * @param {boolean} isLoading Whether the lightbox is loading.
     */
    setLoadingState(isLoading) {
        if (!this.lightbox) return;
        this.lightbox.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }

    /**
     * Update the main image.
     *
     * @param {Object} item The item data.
     */
    updateImage(item) {
        if (!(this.mainImage instanceof HTMLImageElement)) return;

        const removeAriaBusy = () => this.setLoadingState(false);

        this.mainImage.addEventListener('load', removeAriaBusy, { once: true });
        this.mainImage.addEventListener('error', removeAriaBusy, { once: true });

        this.mainImage.src = item.mainImageSrc;
        this.mainImage.alt = item.alt || '';
    }

    /**
     * Update the captions.
     *
     * @param {Object} item The item data.
     */
    updateCaptions(item) {
        if (this.selectedCaptionMain) {
            this.selectedCaptionMain.textContent = item.heading || '';
        }
        if (this.selectedCaptionSecondary) {
            this.selectedCaptionSecondary.innerHTML = item.subheading || '';
        }
    }

    /**
     * Update the counter and screen reader announcement.
     *
     * @param {number} index The current index.
     * @param {Object} item The current item data.
     */
    updateCounter(index, item) {
        const current = index + 1;
        const total = this.totalItems;

        // Update visual counter
        if (this.currentCountEl) {
            this.currentCountEl.textContent = current;
        }

        // Update screen reader announcement
        if (this.announcementEl) {
            const percentage = Math.round((current / total) * 100);
            const parts = [`Image ${current}/${total} (${percentage}%)`];

            // Add caption if available
            if (item.heading) {
                parts.push(item.heading);
            }

            // Add alt text if available
            if (item.alt) {
                parts.push(item.alt);
            }

            this.announcementEl.textContent = parts.join(': ');
        }
    }

    /**
     * Update thumbnail states.
     *
     * @param {number} index The current lightboxIndex.
     */
    updateThumbnailStates(index) {
        this.thumbnailButtons.forEach((button) => {
            const isActive = button.dataset.lightboxIndex === String(index);
            button.setAttribute('aria-current', isActive ? 'true' : 'false');
        });
    }

    /**
     * Scroll to the active thumbnail.
     *
     * @param {number} index The current lightboxIndex.
     * @param {boolean} focusThumb Whether to focus the thumbnail.
     */
    scrollToActiveThumbnail(index, focusThumb) {
        const activeThumbnail = this.thumbnailButtons.find((button) => button.dataset.lightboxIndex === String(index));
        if (!activeThumbnail) return;

        activeThumbnail.scrollIntoView({ block: 'nearest', inline: 'center' });
        if (focusThumb) {
            activeThumbnail.focus({ preventScroll: true });
        }
    }

    /**
     * Update the thumbnails navigation buttons state.
     */
    updateThumbnailsNavigationState() {
        if (!this.thumbnailsList || !this.thumbnailsPrevious || !this.thumbnailsNext) return;

        if (this.isMobile) {
            const { scrollLeft, scrollWidth, clientWidth } = this.thumbnailsList;
            this.thumbnailsPrevious.disabled = scrollLeft <= 0;
            this.thumbnailsNext.disabled = scrollLeft + clientWidth >= scrollWidth - 1;
        } else {
            const { scrollTop, scrollHeight, clientHeight } = this.thumbnailsList;
            this.thumbnailsPrevious.disabled = scrollTop <= 0;
            this.thumbnailsNext.disabled = scrollTop + clientHeight >= scrollHeight - 1;
        }
    }

    /**
     * Scroll the thumbnails list.
     *
     * @param {number} direction The direction to scroll (-1 or 1).
     */
    scrollThumbs(direction) {
        if (!this.thumbnailsList) return;

        const scrollAmount = this.calculateThumbnailScrollAmount();

        if (this.isMobile) {
            this.thumbnailsList.scrollBy({ left: scrollAmount * direction, behavior: 'smooth' });
        } else {
            this.thumbnailsList.scrollBy({ top: scrollAmount * direction, behavior: 'smooth' });
        }
        this.updateThumbnailsNavigationState();
    }

    /**
     * Calculate the scroll amount for thumbnails.
     *
     * @returns {number} The scroll amount in pixels.
     */
    calculateThumbnailScrollAmount() {
        const visibleCount = this.getCSSVariable('--gallery--lightbox--thumbnails--visible-count');
        const thumbnailSize = this.getCSSVariable('--gallery--lightbox--thumbnails--size');
        const gap = this.getCSSVariable('column-gap', this.thumbnailsList);

        return (visibleCount - 1) * thumbnailSize + (visibleCount - 1) * gap;
    }

    // ====================================
    // Data Methods
    // ====================================

    /**
     * Extract item data from card button.
     *
     * @param {HTMLButtonElement} cardButton The card button element.
     * @returns {Object} The item data.
     */
    /* eslint-disable-next-line class-methods-use-this */
    getItemData(cardButton) {
        const mainImage = cardButton.adjacentElementSibling;
        return {
            mainImageSrc: cardButton.dataset.mainImageSrc || '',
            alt: mainImage?.alt || '',
            heading: cardButton.dataset.captionMain || '',
            subheading: cardButton.dataset.captionSecondary || '',
        };
    }

    // ====================================
    // Preloading
    // ====================================

    /**
     * Preload neighboring images.
     *
     * @param {number} index The current index.
     */
    preloadNeighbors(index) {
        if (this.preloadSpan === 0) return;

        /* eslint-disable-next-line no-plusplus */
        for (let d = 1; d <= this.preloadSpan; d++) {
            const top = this.prevIndex(index - (d - 1), this.loop);
            const bottom = this.nextIndex(index + (d - 1), this.loop);

            [top, bottom].forEach((i) => {
                if (!this.preloadSeen.has(i)) {
                    this.preloadSeen.add(i);
                    const imageItem = this.itemsByLightboxIndex[i];
                    if (!imageItem || !imageItem.mainImageSrc) return;

                    const image = new Image();
                    image.src = imageItem.mainImageSrc;
                }
            });
        }
    }

    // ====================================
    // Event Handlers
    // ====================================


    /**
     * Handle global keyboard events.
     *
     * @param {KeyboardEvent} e The keyboard event.
     */
    handleGlobalKeys(e) {
        if (!this.isOpen) return;

        // Don't hijack when user is typing
        const tag = document.activeElement?.tagName;
        const editable = document.activeElement?.isContentEditable;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || editable) return;

        const keyActions = {
            Escape: () => this.closeLightbox(),
            ArrowDown: () => this.next(),
            ArrowUp: () => this.prev(),
            Home: () => this.goTo(this.getMinLightboxIndex()),
            End: () => this.goTo(this.getMaxLightboxIndex()),
        };

        if (keyActions[e.key]) {
            e.preventDefault();
            keyActions[e.key]();
        }
    }

    /**
     * Handle keyboard navigation for thumbnails (roving tabindex).
     *
     * @param {KeyboardEvent} e The keyboard event.
     */
    handleThumbnailKeys(e) {
        const thumbnailButton = e.target.closest(this.selectors.thumbnailButtons);
        if (!thumbnailButton) return;

        const currentLightboxIndex = thumbnailButton.dataset.lightboxIndex;
        const currentArrayIndex = this.thumbnailButtons.indexOf(thumbnailButton);
        if (currentArrayIndex === -1) return;

        const keyActions = {
            ArrowUp: () => this.focusThumbnail(this.prevIndex(currentArrayIndex, this.loop)),
            ArrowDown: () => this.focusThumbnail(this.nextIndex(currentArrayIndex, this.loop)),
            Home: () => this.focusThumbnail(0),
            End: () => this.focusThumbnail(this.thumbnailButtons.length - 1),
            Enter: () => this.goTo(currentLightboxIndex, { focusThumb: true }),
            ' ': () => this.goTo(currentLightboxIndex, { focusThumb: true }),
        };

        if (keyActions[e.key]) {
            e.preventDefault();
            keyActions[e.key]();
        }
    }

    /**
     * Handle focus trap within lightbox.
     *
     * @param {KeyboardEvent} e The keyboard event.
     */
    handleFocusTrap(e) {
        if (!this.isOpen || e.key !== 'Tab') return;

        const focusables = this.getFocusableElements(this.lightbox);
        if (!focusables.length) return;

        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        const current = document.activeElement;

        if (e.shiftKey && current === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && current === last) {
            e.preventDefault();
            first.focus();
        }
    }

    // ====================================
    // Utility Methods
    // ====================================

    /**
     * Get sorted array of lightboxIndex values.
     *
     * @returns {number[]} Sorted array of lightboxIndex values.
     */
    getSortedLightboxIndices() {
        return Object.keys(this.itemsByLightboxIndex)
            .map(Number)
            .sort((a, b) => a - b);
    }

    /**
     * Find a card button by its lightboxIndex.
     *
     * @param {number} lightboxIndex The lightboxIndex to find.
     * @returns {HTMLElement|null} The button element or null.
     */
    findCardButtonByLightboxIndex(lightboxIndex) {
        return this.triggerCardButtons.find((button) => button.dataset.lightboxIndex === String(lightboxIndex)) || null;
    }

    /**
     * Get the maximum lightboxIndex.
     *
     * @returns {number} The maximum lightboxIndex.
     */
    getMaxLightboxIndex() {
        const indices = this.getSortedLightboxIndices();
        return indices.length > 0 ? indices[indices.length - 1] : 0;
    }

    /**
     * Get the minimum lightboxIndex.
     *
     * @returns {number} The minimum lightboxIndex.
     */
    getMinLightboxIndex() {
        const indices = this.getSortedLightboxIndices();
        return indices.length > 0 ? indices[0] : 0;
    }

    /**
     * Get a CSS variable value.
     *
     * @param {string} varName The CSS variable name.
     * @param {HTMLElement} element The element to get the value from (default: this.element).
     * @returns {number} The parsed integer value.
     */
    getCSSVariable(varName, element = this.element) {
        const value = getComputedStyle(element).getPropertyValue(varName);
        return parseInt(value, 10);
    }

    /**
     * Focus a specific thumbnail.
     *
     * @param {number} index The thumbnail index.
     */
    focusThumbnail(index) {
        const button = this.thumbnailButtons[index];
        if (!button) return;

        button.focus();
        button.scrollIntoView({ block: 'nearest', inline: 'center' });
    }

    /**
     * Get all focusable elements within a scope.
     *
     * @param {HTMLElement} scope The scope element.
     * @returns {HTMLElement[]} Array of focusable elements.
     */
    /* eslint-disable-next-line class-methods-use-this */
    getFocusableElements(scope) {
        return Array.from(
            scope.querySelectorAll(
                [
                    'a[href]',
                    'area[href]',
                    'button:not([disabled])',
                    'input:not([disabled]):not([type="hidden"])',
                    'select:not([disabled])',
                    'textarea:not([disabled])',
                    'details > summary:first-of-type',
                    '[tabindex]:not([tabindex="-1"])',
                ].join(',')
            )
        ).filter((el) => el.offsetParent !== null || el === document.activeElement);
    }

    /**
     * Get the previous lightboxIndex.
     *
     * @param {number} currentIndex The current lightboxIndex.
     * @param {boolean} loop Whether to loop around.
     * @returns {number} The previous lightboxIndex.
     */
    prevIndex(currentIndex, loop = false) {
        const sortedIndices = this.getSortedLightboxIndices();
        if (sortedIndices.length === 0) return 0;

        const currentPos = sortedIndices.indexOf(Number(currentIndex));
        if (currentPos === -1) return sortedIndices[0];

        if (currentPos === 0) {
            return loop ? sortedIndices[sortedIndices.length - 1] : sortedIndices[0];
        }
        return sortedIndices[currentPos - 1];
    }

    /**
     * Get the next lightboxIndex.
     *
     * @param {number} currentIndex The current lightboxIndex.
     * @param {boolean} loop Whether to loop around.
     * @returns {number} The next lightboxIndex.
     */
    nextIndex(currentIndex, loop = false) {
        const sortedIndices = this.getSortedLightboxIndices();
        if (sortedIndices.length === 0) return 0;

        const currentPos = sortedIndices.indexOf(Number(currentIndex));
        if (currentPos === -1) return sortedIndices[0];

        if (currentPos === sortedIndices.length - 1) {
            return loop ? sortedIndices[0] : sortedIndices[sortedIndices.length - 1];
        }
        return sortedIndices[currentPos + 1];
    }

    /**
     * Clamp a lightboxIndex to valid range.
     *
     * @param {number} i The lightboxIndex to clamp.
     * @returns {number} The clamped lightboxIndex (or closest valid one).
     */
    clamp(i) {
        const sortedIndices = this.getSortedLightboxIndices();
        if (sortedIndices.length === 0) return 0;

        // If the index exists, return it
        if (this.itemsByLightboxIndex[i]) return Number(i);

        // Otherwise, find the closest valid index
        const numIndex = Number(i);
        /* eslint-disable-next-line no-plusplus */
        for (let j = 0; j < sortedIndices.length; j++) {
            if (sortedIndices[j] >= numIndex) return sortedIndices[j];
        }

        // If no index is greater, return the last one
        return sortedIndices[sortedIndices.length - 1];
    }

    // ====================================
    // Accessibility Methods
    // ====================================

    /**
     * Toggle body scroll lock.
     */
    /* eslint-disable-next-line class-methods-use-this */
    lockBodyScroll() {
        document.documentElement.classList.toggle('no-scroll');
    }

    /**
     * Set inert state on background elements.
     *
     * @param {boolean} enable Whether to enable inert state.
     */
    setInertiaOnAllOtherElements(enable) {
        if (!this.inertSelector) return;

        const nodes = Array.from(
            document.querySelectorAll(
                '.site-header, .site-footer, .site-main__content > *, .skip-link, .gallery__card button'
            )
        ).filter((n) => n !== this.element);

        nodes.forEach((node) => {
            if (enable) {
                node.setAttribute('aria-hidden', 'true');
                try {
                    node.inert = true;
                } catch {
                    // Inert not supported in older browsers
                }
            } else {
                node.removeAttribute('aria-hidden');
                try {
                    node.inert = false;
                } catch {
                    // Inert not supported in older browsers
                }
            }
        });
    }

    /**
     * Helper to handle the direction of the thumbnail track - which is horizontal on mobile and vertical on desktop.
     * @param {string} direction The direction to handle. Can be 'up', 'down', or 'height'.
     * @returns {string} The direction to use.
     */
    handleThumbnailTrackDirection(direction) {
        switch (direction) {
            case 'up':
                if (this.isMobile) {
                    return 'left';
                }
                return direction;
            case 'down':
                if (this.isMobile) {
                    return 'right';
                }
                return direction;
            case 'height':
                if (this.isMobile) {
                    return 'width';
                }
                return direction;
            default:
                return direction;
        }
    }
}
