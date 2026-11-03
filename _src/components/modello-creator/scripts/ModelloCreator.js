const MAX_STEP = 7;
const UNITS = 18; // A board is 18 steps long.

export default class ModelloCreator {
    constructor(element) {
        this.el = element;
        this.config = JSON.parse(element.dataset.modelloCreator);
        this.i18n = this.config.i18n;

        this.board = element.querySelector('[data-modello-board]');
        this.collection = element.querySelector('[data-modello-collection]');
        this.colour = element.querySelector('[data-modello-colour]');
        this.track = element.querySelector('[data-modello-track]');
        this.presets = [...element.querySelectorAll('[data-modello-preset]')];

        this.rows = [...element.querySelectorAll('[data-modello-row]')].map((el) => ({
            el,
            step: 0,
            flipped: false,
        }));

        this.strips = {};
        this.drag = null;

        this.init();
    }

    init() {
        this.board.addEventListener('pointerdown', (e) => this.onPointerDown(e));
        this.board.addEventListener('pointermove', (e) => this.onPointerMove(e));
        this.board.addEventListener('pointerup', (e) => this.onPointerEnd(e));
        this.board.addEventListener('pointercancel', (e) => this.onPointerEnd(e));
        this.board.addEventListener('keydown', (e) => this.onKeyDown(e));

        // Re-place the boards whenever the board's width changes, including the
        // first time it is laid out. A row measured while hidden is 0px wide.
        new ResizeObserver(() => this.placeAll()).observe(this.board);

        this.collection.addEventListener('change', () => this.refresh());
        this.colour.addEventListener('change', () => this.refresh());

        this.presets.forEach((button, index) => {
            button.addEventListener('click', () => this.applyPreset(index));
        });

        this.el.querySelector('[data-modello-reset]').addEventListener('click', () => {
            this.clearActivePreset();
            this.setSteps([]);
        });

        const page = () => this.track.clientWidth * 0.8;
        this.el.querySelector('[data-modello-prev]').addEventListener('click', () => {
            this.track.scrollBy({ left: -page(), behavior: 'smooth' });
        });
        this.el.querySelector('[data-modello-next]').addEventListener('click', () => {
            this.track.scrollBy({ left: page(), behavior: 'smooth' });
        });

        this.refresh();
        this.setSteps([], false);
    }

    // -------------------------------------------------------------------------
    // Geometry.
    // -------------------------------------------------------------------------
    get unit() {
        return this.rows[0].el.offsetWidth / UNITS;
    }

    stripUrl() {
        return `${this.config.imagesUrl}${this.collection.value}-${this.colour.value}.png`;
    }

    place(row, extra = 0) {
        row.el.style.transform = `translateX(${row.step * this.unit + extra}px) rotate(${row.flipped ? 180 : 0}deg)`;
    }

    placeAll() {
        this.rows.forEach((row) => this.place(row));
    }

    describe(row, index) {
        const { i18n } = this;
        let offset = i18n.centred;

        if (row.step > 0) {
            offset = i18n.right.replace('%s', row.step);
        } else if (row.step < 0) {
            offset = i18n.left.replace('%s', Math.abs(row.step));
        }

        row.el.setAttribute(
            'aria-label',
            i18n.board
                .replace('%1$s', index + 1)
                .replace('%2$s', offset)
                .replace('%3$s', row.flipped ? i18n.flipped : i18n.notFlipped)
        );
    }

    /**
     * Put the rows into the given steps ("3", "-4", "4r" for flipped).
     * Missing entries are centred and unflipped.
     */
    setSteps(steps, animate = true) {
        this.rows.forEach((row, index) => {
            const value = String(steps[index] ?? '0');
            row.flipped = value.includes('r');
            row.step = Number(value.replace('r', ''));
            this.settle(row, animate);
        });
    }

    settle(row, animate = true) {
        if (animate) {
            row.el.classList.add('is-settling');
            setTimeout(() => row.el.classList.remove('is-settling'), 501);
        }

        this.place(row);
        this.describe(row, this.rows.indexOf(row));
    }

    // -------------------------------------------------------------------------
    // Pointer: drag to offset, a quick tap to flip.
    // -------------------------------------------------------------------------
    onPointerDown(e) {
        const row = this.rows.find((r) => r.el === e.target);

        if (!row) {
            return;
        }

        e.preventDefault();
        row.el.setPointerCapture(e.pointerId);
        row.el.classList.add('is-dragging');
        this.drag = { row, x0: e.clientX, t0: Date.now(), moved: false };
        this.clearActivePreset();
    }

    onPointerMove(e) {
        if (!this.drag) {
            return;
        }

        const dx = e.clientX - this.drag.x0;

        if (Math.abs(dx) > 5) {
            this.drag.moved = true;
        }

        this.place(this.drag.row, dx);
    }

    onPointerEnd(e) {
        if (!this.drag) {
            return;
        }

        const { row, x0, t0, moved } = this.drag;
        this.drag = null;
        row.el.classList.remove('is-dragging');

        if (!moved && Date.now() - t0 < 250) {
            row.flipped = !row.flipped;
        }

        const target = Math.round((row.step * this.unit + (e.clientX - x0)) / this.unit);
        row.step = Math.max(-MAX_STEP, Math.min(MAX_STEP, target));
        this.settle(row);
    }

    // -------------------------------------------------------------------------
    // Keyboard: arrows move a board, space or enter flips it, up and down
    // change board. One tab stop for the whole board.
    // -------------------------------------------------------------------------
    onKeyDown(e) {
        const index = this.rows.findIndex((r) => r.el === e.target);

        if (index < 0) {
            return;
        }

        const row = this.rows[index];
        let handled = true;

        switch (e.key) {
            case 'ArrowLeft':
                row.step = Math.max(-MAX_STEP, row.step - 1);
                break;
            case 'ArrowRight':
                row.step = Math.min(MAX_STEP, row.step + 1);
                break;
            case ' ':
            case 'Enter':
                row.flipped = !row.flipped;
                break;
            case 'ArrowUp':
            case 'ArrowDown': {
                const next = this.rows[index + (e.key === 'ArrowUp' ? -1 : 1)];

                if (next) {
                    this.rows.forEach((r) => r.el.setAttribute('tabindex', r === next ? '0' : '-1'));
                    next.el.focus();
                }
                break;
            }
            default:
                handled = false;
        }

        if (handled) {
            e.preventDefault();
            this.clearActivePreset();
            this.settle(row);
        }
    }

    // -------------------------------------------------------------------------
    // Presets.
    // -------------------------------------------------------------------------
    clearActivePreset() {
        this.presets.forEach((p) => {
            p.classList.remove('is-active');
            p.setAttribute('aria-pressed', 'false');
        });
    }

    applyPreset(index) {
        this.clearActivePreset();
        this.presets[index].classList.add('is-active');
        this.presets[index].setAttribute('aria-pressed', 'true');
        this.setSteps(this.config.presets[index]);
    }

    loadStrip(src) {
        this.strips[src] ??= new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.src = src;
        });

        return this.strips[src];
    }

    async refresh() {
        const src = this.stripUrl();
        this.rows.forEach((row) => {
            row.el.style.backgroundImage = `url(${src})`;
        });

        const img = await this.loadStrip(src);
        const prefix = this.collection.value === 'linear' ? 1 : 11;

        this.presets.forEach((button, index) => {
            button.querySelector('.modello-creator__preset-label').textContent =
                `${this.i18n.pattern} ${prefix}.${button.dataset.patternId}`;
            this.drawThumb(button.querySelector('canvas'), img, this.config.presets[index]);
        });
    }

    drawThumb(canvas, img, steps) {
        const ctx = canvas.getContext('2d');
        const w = canvas.width;
        const h = canvas.height / this.config.rows;
        const unit = w / UNITS;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        steps.slice(0, this.config.rows).forEach((value, i) => {
            ctx.save();
            ctx.translate(w / 2 + Number(value.replace('r', '')) * unit, i * h + h / 2);

            if (value.includes('r')) {
                ctx.rotate(Math.PI);
            }

            // Three copies so the offset board's ends meet its neighbours.
            [-1, 0, 1].forEach((k) => ctx.drawImage(img, -w / 2 + k * w, -h / 2, w, h));
            ctx.restore();
        });
    }
}
