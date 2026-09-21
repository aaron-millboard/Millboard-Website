/**
 * Filtering, sorting and paging for the shop category grid.
 *
 * Everything works on cards that are already in the page. Nothing is fetched
 * and no URL changes, so the page stays indexable and cannot spawn the filtered
 * URLs that strain index coverage on this site.
 *
 * The controls are hidden in the markup and revealed here, so a visitor without
 * JavaScript sees the full category rather than dead buttons.
 */
export default class CategoryGrid {
    constructor(element) {
        this.el = element;
        this.list = this.el.querySelector('[data-grid-items]');
        this.items = this.list ? [...this.list.children] : [];

        if (this.items.length === 0) {
            return;
        }

        this.perPage = parseInt(this.el.dataset.perPage, 10) || 12;
        this.chips = [...this.el.querySelectorAll('[data-filter-group]')];
        this.sort = this.el.querySelector('[data-grid-sort]');
        this.moreWrap = this.el.querySelector('[data-grid-more-wrap]');
        this.moreButton = this.el.querySelector('[data-grid-more]');
        this.progress = this.el.querySelector('[data-grid-progress]');
        this.empty = this.el.querySelector('[data-grid-empty]');
        this.count = this.el.querySelector('[data-grid-count]');
        this.status = this.el.querySelector('[data-grid-status]');
        this.clearButtons = [...this.el.querySelectorAll('[data-grid-clear]')];

        this.active = new Map();
        this.shown = this.perPage;
        this.strings = this.readStrings();

        this.init();
    }

    /**
     * Wording comes from PHP so it stays translatable.
     */
    readStrings() {
        const fallback = {
            count_one: '{n} product',
            count_many: '{n} products',
            progress: 'Showing {n} of {total}',
            status_one: '{n} product matches',
            status_many: '{n} products match',
        };

        try {
            return { ...fallback, ...JSON.parse(this.el.dataset.strings || '{}') };
        } catch (error) {
            return fallback;
        }
    }

    format(key, n, total) {
        return String(this.strings[key] || '')
            .replace('{n}', n)
            .replace('{total}', total);
    }

    init() {
        this.reveal('[data-grid-filters]');
        this.reveal('[data-grid-sort-wrap]');

        this.chips.forEach((chip) => {
            chip.addEventListener('click', () => this.toggleChip(chip));
        });

        if (this.sort) {
            this.sort.addEventListener('change', () => {
                this.applySort();
                this.render();
            });
        }

        if (this.moreButton) {
            this.moreButton.addEventListener('click', () => {
                this.shown += this.perPage;
                this.render();
                this.focusFirstNewItem();
            });
        }

        this.clearButtons.forEach((button) => {
            button.addEventListener('click', () => this.clear());
        });

        this.render();
    }

    reveal(selector) {
        const el = this.el.querySelector(selector);

        if (el) {
            el.hidden = false;
        }
    }

    toggleChip(chip) {
        const group = chip.dataset.filterGroup;
        const value = chip.dataset.filterValue;
        const current = this.active.get(group) || new Set();

        if (current.has(value)) {
            current.delete(value);
        } else {
            current.add(value);
        }

        if (current.size === 0) {
            this.active.delete(group);
        } else {
            this.active.set(group, current);
        }

        chip.setAttribute('aria-pressed', current.has(value) ? 'true' : 'false');

        this.shown = this.perPage;
        this.render();
    }

    clear() {
        this.active.clear();
        this.chips.forEach((chip) => chip.setAttribute('aria-pressed', 'false'));
        this.shown = this.perPage;
        this.render();
    }

    matches(item) {
        const card = item.firstElementChild;

        if (!card) {
            return false;
        }

        // Within a group the values are alternatives, across groups they all
        // have to hold. Picking two colours widens, adding a width narrows.
        for (const [group, values] of this.active) {
            const raw = card.dataset[`filter${group.charAt(0).toUpperCase()}${group.slice(1)}`] || '';
            const owned = raw.split(' ').filter(Boolean);

            if (![...values].some((value) => owned.includes(value))) {
                return false;
            }
        }

        return true;
    }

    applySort() {
        const mode = this.sort ? this.sort.value : 'default';

        const sorted = [...this.items].sort((a, b) => {
            const cardA = a.firstElementChild;
            const cardB = b.firstElementChild;

            if (mode === 'price-asc' || mode === 'price-desc') {
                // A product with no price sorts last either way, rather than
                // pretending to be free.
                const priceA = parseFloat(cardA.dataset.sortPrice);
                const priceB = parseFloat(cardB.dataset.sortPrice);
                const hasA = !Number.isNaN(priceA);
                const hasB = !Number.isNaN(priceB);

                if (!hasA && !hasB) return 0;
                if (!hasA) return 1;
                if (!hasB) return -1;

                return mode === 'price-asc' ? priceA - priceB : priceB - priceA;
            }

            return (cardA.dataset.sortName || '').localeCompare(cardB.dataset.sortName || '');
        });

        sorted.forEach((item) => this.list.appendChild(item));
        this.items = sorted;
    }

    render() {
        const matching = this.items.filter((item) => this.matches(item));

        this.items.forEach((item) => {
            item.hidden = true;
        });

        matching.slice(0, this.shown).forEach((item) => {
            item.hidden = false;
        });

        const total = matching.length;
        const visible = Math.min(this.shown, total);

        if (this.count) {
            this.count.textContent = this.format(total === 1 ? 'count_one' : 'count_many', total, total);
        }

        if (this.empty) {
            this.empty.hidden = total !== 0;
        }

        if (this.moreWrap) {
            this.moreWrap.hidden = total === 0 || visible >= total;
        }

        if (this.progress) {
            this.progress.textContent = this.format('progress', visible, total);
        }

        this.clearButtons.forEach((button) => {
            if (button.hasAttribute('data-grid-clear') && button.classList.contains('category-grid__clear')) {
                button.hidden = this.active.size === 0;
            }
        });

        if (this.status) {
            this.status.textContent = this.format(total === 1 ? 'status_one' : 'status_many', total, total);
        }
    }

    focusFirstNewItem() {
        const revealed = this.items.filter((item) => !item.hidden);
        const target = revealed[Math.max(0, revealed.length - this.perPage)];
        const link = target ? target.querySelector('a') : null;

        if (link) {
            link.focus();
        }
    }
}
