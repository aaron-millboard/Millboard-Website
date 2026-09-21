/**
 * Tab behaviour for the category page intro copy.
 *
 * Every panel is rendered open. This class is what closes the inactive ones,
 * so with no JavaScript the whole introduction is still readable rather than
 * collapsing to one panel with no way to reach the rest.
 */
export default class IntroTabs {
    constructor(element) {
        this.el = element;
        this.tabs = [...this.el.querySelectorAll('[role="tab"]')];
        this.panels = this.tabs
            .map((tab) => this.el.querySelector(`#${CSS.escape(tab.getAttribute('aria-controls'))}`))
            .filter(Boolean);

        if (this.tabs.length < 2 || this.tabs.length !== this.panels.length) {
            return;
        }

        this.init();
    }

    init() {
        this.el.classList.add('is-enhanced');

        this.tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => this.select(index));
            tab.addEventListener('keydown', (event) => this.onKeydown(event, index));
        });

        const active = this.tabs.findIndex((tab) => tab.getAttribute('aria-selected') === 'true');

        this.select(active === -1 ? 0 : active, false);
    }

    onKeydown(event, index) {
        const lastIndex = this.tabs.length - 1;
        let next = null;

        switch (event.key) {
            case 'ArrowRight':
                next = index === lastIndex ? 0 : index + 1;
                break;
            case 'ArrowLeft':
                next = index === 0 ? lastIndex : index - 1;
                break;
            case 'Home':
                next = 0;
                break;
            case 'End':
                next = lastIndex;
                break;
            default:
                return;
        }

        event.preventDefault();
        this.select(next);
    }

    select(index, moveFocus = true) {
        this.tabs.forEach((tab, i) => {
            const isActive = i === index;

            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.setAttribute('tabindex', isActive ? '0' : '-1');
            tab.classList.toggle('is-active', isActive);

            this.panels[i].classList.toggle('is-active', isActive);
            this.panels[i].hidden = !isActive;
        });

        if (moveFocus) {
            this.tabs[index].focus();
        }
    }
}
