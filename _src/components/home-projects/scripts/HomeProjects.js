/**
 * Home projects rail.
 *
 * Two jobs: say whether the rail can actually be scrolled, so the "scroll
 * sideways" line only appears when it is true, and turn a mouse wheel over the
 * rail into sideways movement. Four cards overflow a laptop and fit a 1920
 * screen, and the card count is editorial, so the first cannot be a media
 * query.
 *
 * Everything else about the rail is native scrolling. The cards are links, so
 * tabbing through them scrolls the rail without any help from here.
 */
export default class HomeProjects {
    constructor(element) {
        this.el = element;
        this.rail = this.el.querySelector('.home-projects__rail');
        this.track = this.el.querySelector('.home-projects__track');

        if (!this.rail || !this.track) {
            return;
        }

        this.scrollable = false;

        this.update = this.update.bind(this);
        this.onWheel = this.onWheel.bind(this);
        this.update();

        window.addEventListener('resize', this.update);

        // Not passive: this one calls preventDefault, and a passive listener
        // that does so is ignored with a console warning.
        this.rail.addEventListener('wheel', this.onWheel, { passive: false });

        // Fonts and images land after first paint and both change the track's
        // width, so the first answer is not always the right one.
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(this.update).catch(() => {});
        }

        this.el.querySelectorAll('img').forEach((image) => {
            if (!image.complete) {
                image.addEventListener('load', this.update, { once: true });
            }
        });
    }

    update() {
        // A pixel of slack: sub-pixel widths otherwise report an overflow that
        // no one can actually scroll.
        this.scrollable = this.track.scrollWidth - this.rail.clientWidth > 1;

        this.el.classList.toggle('home-projects--scrollable', this.scrollable);
    }

    /**
     * Send a wheel over the rail sideways instead of down the page.
     *
     * A mouse has one wheel and this rail only moves in the other direction,
     * so without this the cards are reachable by dragging the bar, swiping a
     * trackpad or tabbing, and not at all by the thing most people have in
     * their hand.
     *
     * Two things it deliberately does not do. It leaves a trackpad's sideways
     * swipe alone, because that already scrolls the rail natively and remapping
     * it would double the movement. And at either end it stops intercepting and
     * lets the page scroll on, so the rail cannot trap the page: reaching the
     * last card and carrying on spinning should carry on down the page, which
     * is what `overscroll-behavior-x: contain` alone does not give you.
     */
    onWheel(event) {
        if (!this.scrollable) {
            return;
        }

        // Already a sideways gesture. Native scrolling has it.
        if (Math.abs(event.deltaX) > Math.abs(event.deltaY)) {
            return;
        }

        // Wheels report in pixels, lines or pages depending on the device and
        // the browser. Only the first is usable as-is.
        const lines = event.deltaMode === 1;
        const pages = event.deltaMode === 2;
        let delta = event.deltaY;

        if (lines) {
            delta *= 16;
        } else if (pages) {
            delta *= this.rail.clientWidth;
        }

        const limit = this.track.scrollWidth - this.rail.clientWidth;
        const atStart = this.rail.scrollLeft <= 0;
        const atEnd = this.rail.scrollLeft >= limit - 1;

        // Hand the page back at the ends rather than swallowing the gesture.
        if ((delta < 0 && atStart) || (delta > 0 && atEnd)) {
            return;
        }

        event.preventDefault();
        this.rail.scrollLeft += delta;
    }
}
