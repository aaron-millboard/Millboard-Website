/**
 * Home story film.
 *
 * The iframe ships with an empty src and is filled on the first click, so a
 * visitor who never reaches this section never fetches a player. Once filled
 * the button is done: the player owns its own controls from then on.
 */
export default class HomeStory {
    constructor(element) {
        this.el = element;
        this.button = this.el.querySelector('[data-home-story-play]');
        this.iframe = this.el.querySelector('.home-story__iframe');

        if (!this.button || !this.iframe) {
            return;
        }

        this.button.addEventListener('click', () => this.play());
    }

    play() {
        const url = this.button.getAttribute('data-embed-url');

        if (!url) {
            return;
        }

        this.iframe.src = url;
        this.el.classList.add('home-story--playing');

        // Focus moves to the player so a keyboard user is not left on a button
        // that has just been hidden underneath it.
        this.iframe.focus({ preventScroll: true });
    }
}
