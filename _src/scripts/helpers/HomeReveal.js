/**
 * Reveal-on-scroll for the homepage redesign's blocks.
 *
 * A block reveals as a unit. When the first of its elements comes into view the
 * whole block is released together, and the order within it comes from each
 * element's own `--mbh-reveal-delay`.
 *
 * That grouping is the point. Observing each element separately, which is what
 * this did, meant every element waited for its own crossing -- and in the
 * vision block the heading crosses 34px of scroll before the tiles beside it.
 * A wheel notch is around 100px, so at any real scrolling speed all three
 * arrived in the same frame and the sequence collapsed into a lump; scroll
 * slowly and it came back. The result was an order that changed with how fast
 * you happened to be moving, which is exactly what "it looks random" describes.
 * One trigger per block and the delays do the sequencing, at any speed.
 *
 * Each element is released once and then unobserved. These reveals are
 * deliberately not reversible -- re-hiding content because someone scrolled
 * back up to re-read it is the opposite of helpful.
 *
 * The hiding is this script's responsibility, not the stylesheet's. Nothing on
 * the page is hidden until `mbh-reveal-ready` goes on the root element here,
 * so if this file never runs the home page is simply a page, rather than a
 * blank column below the hero. Everything below is about giving that class
 * back the moment it stops earning its keep.
 */
export default class HomeReveal {
    /**
     * The gap between one element and the next where a block has not asked for
     * a particular rhythm of its own. Long enough to read as a sequence rather
     * than a flicker, short enough that the last item in a row of five is not
     * still arriving after the first has settled.
     */
    static STAGGER = 140;

    constructor() {
        this.selector = '.mbh-reveal';
        this.root = document.documentElement;

        // Asked for no motion: show everything now and never observe anything.
        // Checked before anything is hidden, so there is no work to undo.
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.revealAll();
            return;
        }

        // Nothing to observe with, so leave the page visible.
        if (!('IntersectionObserver' in window)) {
            this.revealAll();
            return;
        }

        // Blocks already released, so a second element of the same block
        // crossing later is ignored rather than restarting its cascade.
        this.released = new WeakSet();

        // From here the stylesheet is allowed to hide things, because this
        // script is now in a position to show them again.
        this.root.classList.add('mbh-reveal-ready');

        this.observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    this.release(entry.target);
                });
            },
            {
                // A hair of the element is enough, but hold off until it is
                // properly on screen rather than just clipping the bottom edge.
                threshold: 0.05,
                rootMargin: '0px 0px -8% 0px',
            }
        );

        this.observe();

        // Blocks can arrive after this runs -- the editor's preview, or anything
        // that renders late. Watching the tree costs nothing while idle and
        // saves a whole class of "why did this one not animate" bugs.
        this.mutationObserver = new MutationObserver(() => this.observe());
        this.mutationObserver.observe(document.body, { childList: true, subtree: true });

        this.guard();
    }

    observe() {
        document.querySelectorAll(`${this.selector}:not(.is-revealed)`).forEach((element) => {
            if (element.dataset.mbhObserved === 'true') {
                return;
            }

            element.dataset.mbhObserved = 'true';
            this.observer.observe(element);
        });
    }

    /**
     * The block an element reveals with.
     *
     * Falls back to the element itself, so anything outside a block still
     * reveals rather than being dropped for want of a group.
     */
    group(element) {
        return element.closest('.wp-block, section, footer') || element;
    }

    /**
     * Release the whole block the given element belongs to.
     *
     * Elements are taken in DOM order, which is reading order, and each one
     * keeps whatever delay its block asked for. Where a block asked for
     * nothing -- most of them set none at all, so every element arrived at
     * once -- a delay is filled in from its position, so the block cascades
     * instead of flashing in as a single lump.
     */
    release(element) {
        const group = this.group(element);

        if (this.released.has(group)) {
            return;
        }

        this.released.add(group);

        const members = group.matches(this.selector)
            ? [group]
            : [...group.querySelectorAll(this.selector)];

        // Fill in a rhythm only for a block that states none of its own.
        //
        // All or nothing, deliberately. Filling in the gaps of a block that
        // has its own timings mixes two scales that know nothing about each
        // other: the story block states 160ms and 300ms for its figures, and
        // a filled-in 140ms for the element above landed 20ms before the 160,
        // which is not a step, it is a collision. Where a block has said
        // anything about its timing it has said all of it, and an element it
        // left at zero is meant to lead.
        const stated = members.some((member) => member.style.getPropertyValue('--mbh-reveal-delay'));

        members.forEach((member, index) => {
            if (!stated) {
                member.style.setProperty('--mbh-reveal-delay', `${index * HomeReveal.STAGGER}ms`);
            }

            member.classList.add('is-revealed');
            this.observer.unobserve(member);
        });
    }

    /**
     * Make sure the observer is actually doing its job.
     *
     * If something is sitting in the viewport a second and a half after load
     * and still has not been released, the observer is not working -- a
     * throttled tab, a browser that reports nothing intersecting, anything
     * unforeseen. Whatever the cause, invisible content is the worst possible
     * answer, so the whole mechanism stands down and the page shows.
     */
    guard() {
        window.setTimeout(() => {
            const onScreenButHidden = [...document.querySelectorAll(`${this.selector}:not(.is-revealed)`)]
                .some((element) => {
                    const box = element.getBoundingClientRect();
                    return box.top < window.innerHeight && box.bottom > 0 && box.height > 0;
                });

            if (onScreenButHidden) {
                this.standDown();
            }
        }, 1500);
    }

    /**
     * Give up on the animation and hand the page back.
     *
     * Dropping the root class is what does the work: with it gone the hidden
     * state in the stylesheet stops applying to everything, including the
     * blocks further down that have not been reached yet.
     */
    standDown() {
        if (this.observer) {
            this.observer.disconnect();
        }

        if (this.mutationObserver) {
            this.mutationObserver.disconnect();
        }

        // Show it without animating it. The rescue cannot itself depend on a
        // transition running, since a transition that will not run is one of
        // the things being rescued from.
        this.root.classList.add('mbh-reveal-instant');
        this.root.classList.remove('mbh-reveal-ready');
    }

    revealAll() {
        document.querySelectorAll(this.selector).forEach((element) => {
            element.classList.add('is-revealed');
        });
    }
}
