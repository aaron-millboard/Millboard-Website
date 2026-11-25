/*!
 * Millboard Modello pattern creator
 * Drag the boards left and right, click them to flip them, or pick a preset pattern.
 * No dependencies. See README.md for installation.
 */
(function () {
    'use strict';

    var SCRIPT_DIR = (document.currentScript && document.currentScript.src || '').replace(/[^/]*$/, '');
    var ROWS = 12;
    var MAX_STEP = 7;
    var UNITS = 18; // A board is 18 steps long.

    // Preset patterns: twelve row offsets in steps, a trailing "r" means the board is flipped.
    var PRESETS = [{"id":"1","steps":["3","-3","3","-3","3","-3","3","-3","3","-3","3","-3"]},{"id":"11","steps":["4","-4","4","-4","4","-4","4","-4","4","-4","4","-4"]},{"id":"12","steps":["-4","0","4","-4","0","4","-4","0","4","-4","0","4"]},{"id":"13","steps":["-4","4","0","4","-4","4","0","4","-4","4","0","4"]},{"id":"14","steps":["-7","1","7","1","-7","1","7","1","-7","1","7","1"]},{"id":"15","steps":["-7","7","1","-1","-7","7","1","-1","-7","7","1","-1"]},{"id":"17","steps":["4","0","2","0","4","-4","4","0","2","0","4","-4"]},{"id":"2","steps":["3r","-3","3r","-3","3r","-3","3r","-3","3r","-3","3r","-3"]},{"id":"21","steps":["4r","-4","4r","-4","4r","-4","4r","-4","4r","-4","4r","-4"]},{"id":"22","steps":["-4","0r","4","-4r","0","4r","-4","0r","4","-4r","0","4r"]},{"id":"23","steps":["-4","4r","0","4r","-4","4r","0","4r","-4","4r","0","4r"]},{"id":"26","steps":["-7","7r","1","-7r","7","1r","-7","7r","1","-7r","7","1r"]},{"id":"3","steps":["3r","-3r","3r","-3","3","-3","3r","-3r","3r","-3","3","-3"]},{"id":"32","steps":["0r","4r","-4r","0","4","-4","0r","4r","-4r","0","4","-4"]},{"id":"37","steps":["4r","2r","-4r","4","2","-4","4r","2r","-4r","4","2","-4"]},{"id":"4","steps":["3r","-3","3","-3r","3","-3","3r","-3","3","-3r","3","-3"]},{"id":"43","steps":["4","0","4r","-4","4","0r","4","-4","4r","0","4","-4r"]},{"id":"45","steps":["7","1","-1r","-7","7","1r","-1","-7","7r","1","-1","-7r"]},{"id":"47","steps":["4r","2","-4","4r","2","-4","4r","2","-4","4r","2","-4"]},{"id":"5","steps":["3r","-3r","3","-3","3r","-3r","3","-3","3r","-3r","3","-3"]},{"id":"53","steps":["4r","0r","4","-4","4r","0r","4","-4","4r","0r","4","-4"]},{"id":"6","steps":["3r","-3r","3r","-3r","3","-3","3","-3","3r","-3r","3r","-3r"]},{"id":"7","steps":["3r","-3","3","-3","3","-3","3r","-3","3","-3","3","-3"]},{"id":"72","steps":["3r","0","-3","3","0","-3","3r","0","-3","3","0","-3"]}];

    var COLOUR_SLUGS = ['antique-oak', 'ashwood', 'brushed-basalt', 'burnt-cedar', 'coppered-oak', 'ebony-grey', 'golden-oak', 'jarrah', 'limed-oak', 'smoked-oak'];

    var ENGLISH = {
        heading: 'Laying patterns',
        intro: 'Offset the same Modello board to create a range of laying patterns. Choose a pattern below, or drag and flip the boards to design your own.',
        instruction: 'Drag the boards to the left or right, or click on them to flip them.',
        collection: 'Collection',
        colour: 'Colour',
        linear: 'Linear',
        contour: 'Contour',
        reset: 'Reset boards',
        pattern: 'Pattern',
        board: 'Board %1$s, %2$s, %3$s',
        centred: 'centred',
        right: '%s steps right',
        left: '%s steps left',
        flipped: 'flipped',
        notFlipped: 'not flipped',
        prev: 'Previous patterns',
        next: 'Next patterns',
        colours: {
            'antique-oak': 'Antique Oak', 'ashwood': 'Ashwood', 'brushed-basalt': 'Brushed Basalt', 'burnt-cedar': 'Burnt Cedar',
            'coppered-oak': 'Coppered Oak', 'ebony-grey': 'Ebony Grey', 'golden-oak': 'Golden Oak', 'jarrah': 'Jarrah',
            'limed-oak': 'Limed Oak', 'smoked-oak': 'Smoked Oak'
        }
    };

    function extend(base, over) {
        var out = {}, k;
        for (k in base) { out[k] = base[k]; }
        for (k in over) { out[k] = over[k]; }
        return out;
    }

    // Wording as approved for the Millboard sites. Add a language by copying one block.
    var LANGS = {
        en: ENGLISH,
        'en-us': extend(ENGLISH, {
            colour: 'Color',
            centred: 'centered',
            colours: extend(ENGLISH.colours, { 'ebony-grey': 'Ebony Gray' })
        }),
        de: extend(ENGLISH, {
            heading: 'Verlegemuster',
            intro: 'Versetzen Sie dieselbe Modello Diele, um eine Vielzahl von Verlegemustern zu schaffen. Wählen Sie unten ein Muster aus oder ziehen und drehen Sie die Dielen, um Ihr eigenes Muster zu gestalten.',
            instruction: 'Ziehen Sie die Dielen nach links oder rechts oder klicken Sie darauf, um sie zu drehen.',
            collection: 'Kollektion',
            colour: 'Farbe',
            reset: 'Dielen zurücksetzen',
            pattern: 'Muster',
            board: 'Diele %1$s, %2$s, %3$s',
            centred: 'zentriert',
            right: '%s Schritte nach rechts',
            left: '%s Schritte nach links',
            flipped: 'gedreht',
            notFlipped: 'nicht gedreht',
            prev: 'Vorherige Muster',
            next: 'Nächste Muster'
        }),
        fr: extend(ENGLISH, {
            heading: 'Motifs de pose',
            intro: 'Décalez la même lame Modello pour créer toute une gamme de motifs de pose. Choisissez un motif ci-dessous, ou faites glisser et retournez les lames pour composer le vôtre.',
            instruction: 'Faites glisser les lames vers la gauche ou la droite, ou cliquez dessus pour les retourner.',
            collection: 'Collection',
            colour: 'Coloris',
            reset: 'Réinitialiser les lames',
            pattern: 'Motif',
            board: 'Lame %1$s, %2$s, %3$s',
            centred: 'centrée',
            right: '%s pas vers la droite',
            left: '%s pas vers la gauche',
            flipped: 'retournée',
            notFlipped: 'non retournée',
            prev: 'Motifs précédents',
            next: 'Motifs suivants',
            colours: {
                'antique-oak': 'Chêne Antique', 'ashwood': 'Frêne', 'brushed-basalt': 'Basalte Brossé', 'burnt-cedar': 'Cèdre Brûlé',
                'coppered-oak': 'Chêne Cuivré', 'ebony-grey': 'Gris Ebène', 'golden-oak': 'Chêne Doré', 'jarrah': 'Jarrah',
                'limed-oak': 'Chêne Cérusé', 'smoked-oak': 'Chêne Fumé'
            }
        })
    };

    function pickLang(code) {
        code = String(code || 'en').toLowerCase().replace('_', '-');
        if (LANGS[code]) { return LANGS[code]; }
        return LANGS[code.split('-')[0]] || LANGS.en;
    }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function markup(t, o) {
        var colours = COLOUR_SLUGS.map(function (slug) {
            return '<option value="' + slug + '"' + (slug === o.colour ? ' selected' : '') + '>' + esc(t.colours[slug]) + '</option>';
        }).join('');
        var rows = '';
        for (var i = 0; i < ROWS; i++) {
            rows += '<div class="modello-creator__row" role="button" tabindex="' + (i === 0 ? 0 : -1) + '" data-modello-row></div>';
        }
        var presets = PRESETS.map(function (p, i) {
            return '<button class="modello-creator__preset" type="button" data-modello-preset="' + i + '" data-pattern-id="' + p.id + '" aria-pressed="false">' +
                '<canvas class="modello-creator__thumb" width="360" height="240" aria-hidden="true"></canvas>' +
                '<span class="modello-creator__preset-label"></span></button>';
        }).join('');

        return '<div class="modello-creator__inner">' +
            '<div class="modello-creator__intro">' +
            '<hr class="modello-creator__rule" aria-hidden="true">' +
            '<h2 class="modello-creator__heading">' + esc(o.heading || t.heading) + '</h2>' +
            '<div class="modello-creator__copy"><p>' + esc(o.intro || t.intro) + '</p></div></div>' +
            '<div class="modello-creator__controls">' +
            '<label class="modello-creator__field"><span class="modello-creator__label">' + esc(t.collection) + '</span>' +
            '<select class="modello-creator__select" data-modello-collection>' +
            '<option value="linear"' + (o.collection === 'linear' ? ' selected' : '') + '>' + esc(t.linear) + '</option>' +
            '<option value="contour"' + (o.collection === 'contour' ? ' selected' : '') + '>' + esc(t.contour) + '</option></select></label>' +
            '<label class="modello-creator__field"><span class="modello-creator__label">' + esc(t.colour) + '</span>' +
            '<select class="modello-creator__select" data-modello-colour>' + colours + '</select></label></div>' +
            '<p class="modello-creator__instruction">' + esc(o.instruction || t.instruction) + '</p>' +
            '<div class="modello-creator__board" role="group" aria-label="' + esc(o.instruction || t.instruction) + '" data-modello-board>' + rows + '</div>' +
            '<div class="modello-creator__presets">' +
            '<button class="modello-creator__nav modello-creator__nav--prev" type="button" data-modello-prev aria-label="' + esc(t.prev) + '"></button>' +
            '<div class="modello-creator__track" data-modello-track>' + presets + '</div>' +
            '<button class="modello-creator__nav modello-creator__nav--next" type="button" data-modello-next aria-label="' + esc(t.next) + '"></button></div>' +
            '<div class="modello-creator__actions"><button class="modello-creator__reset" type="button" data-modello-reset>' + esc(t.reset) + '</button></div>' +
            '</div>';
    }

    function ModelloCreator(el) {
        var t = pickLang(el.getAttribute('data-lang') || document.documentElement.lang);
        var collection = el.getAttribute('data-collection') === 'linear' ? 'linear' : 'contour';
        var colour = COLOUR_SLUGS.indexOf(el.getAttribute('data-colour')) > -1 ? el.getAttribute('data-colour') : 'antique-oak';

        el.classList.add('modello-creator');
        el.innerHTML = markup(t, {
            collection: collection,
            colour: colour,
            heading: el.getAttribute('data-heading'),
            intro: el.getAttribute('data-intro'),
            instruction: el.getAttribute('data-instruction')
        });

        this.el = el;
        this.t = t;
        this.imagesUrl = (el.getAttribute('data-images') || SCRIPT_DIR + 'img/').replace(/\/?$/, '/');
        this.board = el.querySelector('[data-modello-board]');
        this.collection = el.querySelector('[data-modello-collection]');
        this.colour = el.querySelector('[data-modello-colour]');
        this.track = el.querySelector('[data-modello-track]');
        this.presets = [].slice.call(el.querySelectorAll('[data-modello-preset]'));
        this.rows = [].slice.call(el.querySelectorAll('[data-modello-row]')).map(function (row) {
            return { el: row, step: 0, flipped: false };
        });
        this.strips = {};
        this.drag = null;
        this.init();
    }

    ModelloCreator.prototype = {
        init: function () {
            var self = this;
            this.board.addEventListener('pointerdown', function (e) { self.onPointerDown(e); });
            this.board.addEventListener('pointermove', function (e) { self.onPointerMove(e); });
            this.board.addEventListener('pointerup', function (e) { self.onPointerEnd(e); });
            this.board.addEventListener('pointercancel', function (e) { self.onPointerEnd(e); });
            this.board.addEventListener('keydown', function (e) { self.onKeyDown(e); });

            // Re-place the boards whenever the board's width changes, including the first time it is laid out.
            if (window.ResizeObserver) {
                new ResizeObserver(function () { self.placeAll(); }).observe(this.board);
            } else {
                window.addEventListener('resize', function () { self.placeAll(); });
            }

            this.collection.addEventListener('change', function () { self.refresh(); });
            this.colour.addEventListener('change', function () { self.refresh(); });
            this.presets.forEach(function (button, index) {
                button.addEventListener('click', function () { self.applyPreset(index); });
            });
            this.el.querySelector('[data-modello-reset]').addEventListener('click', function () {
                self.clearActivePreset();
                self.setSteps([]);
            });
            this.el.querySelector('[data-modello-prev]').addEventListener('click', function () {
                self.track.scrollBy({ left: -self.track.clientWidth * 0.8, behavior: 'smooth' });
            });
            this.el.querySelector('[data-modello-next]').addEventListener('click', function () {
                self.track.scrollBy({ left: self.track.clientWidth * 0.8, behavior: 'smooth' });
            });

            this.refresh();
            this.setSteps([], false);
        },

        unit: function () { return this.rows[0].el.offsetWidth / UNITS; },

        stripUrl: function () { return this.imagesUrl + this.collection.value + '-' + this.colour.value + '.png'; },

        place: function (row, extra) {
            row.el.style.transform = 'translateX(' + (row.step * this.unit() + (extra || 0)) + 'px) rotate(' + (row.flipped ? 180 : 0) + 'deg)';
        },

        placeAll: function () {
            var self = this;
            this.rows.forEach(function (row) { self.place(row); });
        },

        describe: function (row, index) {
            var t = this.t, offset = t.centred;
            if (row.step > 0) { offset = t.right.replace('%s', row.step); }
            else if (row.step < 0) { offset = t.left.replace('%s', Math.abs(row.step)); }
            row.el.setAttribute('aria-label', t.board.replace('%1$s', index + 1).replace('%2$s', offset).replace('%3$s', row.flipped ? t.flipped : t.notFlipped));
        },

        // steps: "3", "-4", "4r" (flipped). Missing entries are centred and unflipped.
        setSteps: function (steps, animate) {
            var self = this;
            this.rows.forEach(function (row, index) {
                var value = String(steps[index] == null ? '0' : steps[index]);
                row.flipped = value.indexOf('r') > -1;
                row.step = Number(value.replace('r', ''));
                self.settle(row, animate !== false);
            });
        },

        settle: function (row, animate) {
            if (animate !== false) {
                row.el.classList.add('is-settling');
                setTimeout(function () { row.el.classList.remove('is-settling'); }, 501);
            }
            this.place(row);
            this.describe(row, this.rows.indexOf(row));
        },

        // Pointer: drag to offset, a quick tap to flip.
        onPointerDown: function (e) {
            var row = this.rows.filter(function (r) { return r.el === e.target; })[0];
            if (!row) { return; }
            e.preventDefault();
            row.el.setPointerCapture(e.pointerId);
            row.el.classList.add('is-dragging');
            this.drag = { row: row, x0: e.clientX, t0: Date.now(), moved: false };
            this.clearActivePreset();
        },

        onPointerMove: function (e) {
            if (!this.drag) { return; }
            var dx = e.clientX - this.drag.x0;
            if (Math.abs(dx) > 5) { this.drag.moved = true; }
            this.place(this.drag.row, dx);
        },

        onPointerEnd: function (e) {
            if (!this.drag) { return; }
            var d = this.drag;
            this.drag = null;
            d.row.el.classList.remove('is-dragging');
            if (!d.moved && Date.now() - d.t0 < 250) { d.row.flipped = !d.row.flipped; }
            var target = Math.round((d.row.step * this.unit() + (e.clientX - d.x0)) / this.unit());
            d.row.step = Math.max(-MAX_STEP, Math.min(MAX_STEP, target));
            this.settle(d.row);
        },

        // Keyboard: arrows move a board, space or enter flips it, up and down change board.
        onKeyDown: function (e) {
            var index = -1;
            this.rows.forEach(function (r, i) { if (r.el === e.target) { index = i; } });
            if (index < 0) { return; }
            var row = this.rows[index], handled = true;

            switch (e.key) {
                case 'ArrowLeft': row.step = Math.max(-MAX_STEP, row.step - 1); break;
                case 'ArrowRight': row.step = Math.min(MAX_STEP, row.step + 1); break;
                case ' ':
                case 'Enter': row.flipped = !row.flipped; break;
                case 'ArrowUp':
                case 'ArrowDown':
                    var next = this.rows[index + (e.key === 'ArrowUp' ? -1 : 1)];
                    if (next) {
                        this.rows.forEach(function (r) { r.el.setAttribute('tabindex', r === next ? '0' : '-1'); });
                        next.el.focus();
                    }
                    break;
                default: handled = false;
            }

            if (handled) {
                e.preventDefault();
                this.clearActivePreset();
                this.settle(row);
            }
        },

        clearActivePreset: function () {
            this.presets.forEach(function (p) {
                p.classList.remove('is-active');
                p.setAttribute('aria-pressed', 'false');
            });
        },

        applyPreset: function (index) {
            this.clearActivePreset();
            this.presets[index].classList.add('is-active');
            this.presets[index].setAttribute('aria-pressed', 'true');
            this.setSteps(PRESETS[index].steps);
        },

        loadStrip: function (src) {
            if (!this.strips[src]) {
                this.strips[src] = new Promise(function (resolve) {
                    var img = new Image();
                    img.onload = function () { resolve(img); };
                    img.src = src;
                });
            }
            return this.strips[src];
        },

        refresh: function () {
            var self = this, src = this.stripUrl();
            this.rows.forEach(function (row) { row.el.style.backgroundImage = 'url(' + src + ')'; });
            this.loadStrip(src).then(function (img) {
                var prefix = self.collection.value === 'linear' ? 1 : 11;
                self.presets.forEach(function (button, index) {
                    button.querySelector('.modello-creator__preset-label').textContent = self.t.pattern + ' ' + prefix + '.' + button.dataset.patternId;
                    self.drawThumb(button.querySelector('canvas'), img, PRESETS[index].steps);
                });
            });
        },

        drawThumb: function (canvas, img, steps) {
            var ctx = canvas.getContext('2d'), w = canvas.width, h = canvas.height / ROWS, unit = w / UNITS;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            steps.slice(0, ROWS).forEach(function (value, i) {
                ctx.save();
                ctx.translate(w / 2 + Number(value.replace('r', '')) * unit, i * h + h / 2);
                if (value.indexOf('r') > -1) { ctx.rotate(Math.PI); }
                // Three copies so the offset board's ends meet its neighbours.
                [-1, 0, 1].forEach(function (k) { ctx.drawImage(img, -w / 2 + k * w, -h / 2, w, h); });
                ctx.restore();
            });
        }
    };

    function start() {
        [].slice.call(document.querySelectorAll('[data-modello-creator]')).forEach(function (el) {
            if (!el.__modelloCreator) { el.__modelloCreator = new ModelloCreator(el); }
        });
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
}());
