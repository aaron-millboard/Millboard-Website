/**
 * Advice filter bar.
 *
 * Filters the category's article grid by topic, in place. Every guide is
 * already on the page, each card naming the topics it belongs to, so a chip
 * only hides the cards outside its topic; nothing is fetched.
 *
 * The chosen topic is kept in the address as ?topic=<slug>, replaced rather
 * than pushed, so Back still leaves the page in one step, and coming back to it
 * from a guide opens on the same topic.
 */
export default class AdviceFilterBar {
    constructor(element) {
        this.el = element;
        this.chips = Array.from(this.el.querySelectorAll('[data-advice-topic]'));
        this.status = this.el.querySelector('[data-advice-filter-status]');
        this.grid = document.querySelector('.advice-article-grid');

        if (!this.grid || this.chips.length < 2) {
            return;
        }

        this.cards = Array.from(this.grid.querySelectorAll('[data-advice-topics]'));
        this.featured = Array.from(document.querySelectorAll('.advice-featured-guide'));

        this.chips.forEach((chip) => {
            chip.addEventListener('click', () => this.select(chip, true));
        });

        const slug = new URLSearchParams(window.location.search).get('topic');
        const chosen = slug ? this.chips.find((chip) => chip.dataset.adviceSlug === slug) : null;

        if (chosen) {
            this.select(chosen, false);
        }
    }

    select(chip, fromClick) {
        const topic = chip.dataset.adviceTopic;
        let shown = 0;

        this.cards.forEach((card) => {
            const match = !topic || card.dataset.adviceTopics.split(' ').includes(topic);

            card.hidden = !match;
            shown += match ? 1 : 0;
        });

        this.chips.forEach((other) => {
            other.setAttribute('aria-pressed', String(other === chip));
        });

        // The featured guide belongs to the category as a whole, so it goes
        // while one topic is chosen, as the design has it.
        this.featured.forEach((featured) => {
            featured.hidden = Boolean(topic);
        });

        if (this.status && this.status.dataset.template) {
            this.status.textContent = this.status.dataset.template.replace('{shown}', shown.toLocaleString());
        }

        if (fromClick) {
            this.remember(chip.dataset.adviceSlug);
        }
    }

    remember(slug) {
        if (!window.history || !window.history.replaceState) {
            return;
        }

        const url = new URL(window.location.href);

        if (slug) {
            url.searchParams.set('topic', slug);
        } else {
            url.searchParams.delete('topic');
        }

        window.history.replaceState(window.history.state, '', url.toString());
    }
}
