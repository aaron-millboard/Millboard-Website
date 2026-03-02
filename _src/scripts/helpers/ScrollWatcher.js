import throttle from 'lodash.throttle';

export default class ScrollWatcher {
    constructor() {
        if (!!window.ScrollWatcher) {
            return window.ScrollWatcher;
        }

        // Scroll direction management.
        this.prevScrollTop = window.pageYOffset || document.documentElement.scrollTop;
        this.prevScrollDirection = '';

        window.addEventListener('scroll', throttle(this.onscroll, 500));
        window.addEventListener('scrollup', this);
        window.addEventListener('scrolldown', this);

        window.ScrollWatcher = this;

        return this;
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

    onscroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        if (scrollTop > this.prevScrollTop && this.prevScrollDirection !== 'down') {
            this.prevScrollDirection = 'down';
            window.dispatchEvent(ScrollWatcher.events.scrolldown);
        } else if (scrollTop < this.prevScrollTop && this.prevScrollDirection !== 'up') {
            this.prevScrollDirection = 'up';
            window.dispatchEvent(ScrollWatcher.events.scrollup);
        }

        this.prevScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }

    onscrollup() {
        window.dispatchEvent(ScrollWatcher.events.scrollchangeup);
    }

    onscrolldown() {
        window.dispatchEvent(ScrollWatcher.events.scrollchangedown);
    }

    // Custom events for state change listeners.
    static events = {
        get scrollup() {
            return new Event('scrollup');
        },
        get scrolldown() {
            return new Event('scrolldown');
        },
        get scrollchangeup() {
            return new CustomEvent('scrollchange', {
                detail: {
                    direction: 'up',
                }
            });
        },
        get scrollchangedown() {
            return new CustomEvent('scrollchange', {
                detail: {
                    direction: 'down',
                }
            });
        },
    };
}
