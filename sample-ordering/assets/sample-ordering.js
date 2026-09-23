/* Millboard Internal Sample Ordering — WooCommerce basket build
 *
 * Builds 22 category accordions over the site's WooCommerce sample products,
 * with quantity steppers and search, and adds the selection to the basket.
 *
 * Data arrives as window.MB_SOF_DATA from PHP:
 *   { catalogue: [{id, sku, name, category, in_stock}], categories: [...],
 *     maxQty: 99, cartUrl: "..." }
 *
 * Every DOM lookup is scoped to the widget container, so generic class names
 * such as .step or .accordion cannot match the surrounding theme markup.
 */
(function () {
  'use strict';

  var ROOT = document.getElementById('mb-sof');
  if (!ROOT) return;
  if (ROOT.dataset.mbSofReady) return;
  ROOT.dataset.mbSofReady = '1';

  var DATA = window.MB_SOF_DATA || {};
  var CATALOGUE = DATA.catalogue || [];
  var CATEGORY_ORDER = DATA.categories || [];
  var MAX_QTY = DATA.maxQty || 99;

  // MILLBOARD EDIT - each line carries its own ceiling.
  // The portal caps every SKU individually (1, 3, 5, 10, 22 and 30 are all in
  // use, and two colours of one board can differ), so MAX_QTY is only the
  // fallback for anything the limits file does not name.
  var MAX_BY_ID = (function () {
    var m = {};
    (DATA.catalogue || []).forEach(function (p) {
      if (p && p.max_qty != null) m[p.id] = parseInt(p.max_qty, 10) || 0;
    });
    return m;
  }());

  function maxFor(id) {
    return MAX_BY_ID[id] != null ? MAX_BY_ID[id] : MAX_QTY;
  }

  function byId(id) { return ROOT.querySelector('#' + id); }
  function qs(sel) { return ROOT.querySelector(sel); }
  function qsa(sel) { return ROOT.querySelectorAll(sel); }

  /** Quantities, keyed by WooCommerce product id. The source of truth. */
  var order = {};

  // ── Helpers ────────────────────────────────────────────────────────────────

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function highlightText(text, query) {
    if (!query) return escapeHtml(text);
    var safe = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return escapeHtml(text).replace(new RegExp('(' + safe + ')', 'gi'), '<em>$1</em>');
  }

  function productsIn(category) {
    return CATALOGUE.filter(function (p) { return p.category === category; });
  }

  // ── Build ──────────────────────────────────────────────────────────────────

  function productRowHtml(p, query) {
    var qty = order[p.id] || 0;

    if (!p.in_stock) {
      // WooCommerce would refuse add_to_cart() for these, so do not offer a
      // stepper that leads to a failure notice on the cart page.
      return (
        '<div class="product-row is-unavailable" data-id="' + p.id + '">' +
          '<div class="product-info">' +
            '<div class="product-name">' + highlightText(p.name, query) + '</div>' +
            '<div class="product-sku">' + highlightText(p.sku, query) + '</div>' +
          '</div>' +
          '<div class="product-unavailable">Out of stock</div>' +
        '</div>'
      );
    }

    return (
      '<div class="product-row" data-id="' + p.id + '">' +
        '<div class="product-info">' +
          '<div class="product-name">' + highlightText(p.name, query) + '</div>' +
          '<div class="product-sku">' + highlightText(p.sku, query) + '</div>' +
        '</div>' +
        '<div class="stepper' + (qty > 0 ? ' has-qty' : '') + '" data-id="' + p.id + '">' +
          '<button type="button" class="stepper-btn" data-action="dec" data-id="' + p.id + '"' +
            (qty === 0 ? ' disabled' : '') + ' aria-label="Decrease">&minus;</button>' +
          '<input class="stepper-qty" type="number" min="0" max="' + maxFor(p.id) + '" value="' + qty + '"' +
            ' data-id="' + p.id + '" aria-label="Quantity for ' + escapeHtml(p.name) + '">' +
          '<button type="button" class="stepper-btn" data-action="inc" data-id="' + p.id + '"' +
            ' aria-label="Increase">+</button>' +
        '</div>' +
      '</div>'
    );
  }

  function bodyHtml(products, query) {
    if (products.length === 0) {
      return '<div class="empty-category">No products in this category yet.</div>';
    }
    return products.map(function (p) { return productRowHtml(p, query); }).join('');
  }

  function buildAccordions() {
    var container = byId('accordion-container');
    container.innerHTML = '';

    CATEGORY_ORDER.forEach(function (cat, idx) {
      var products = productsIn(cat);
      var count = products.length;

      var div = document.createElement('div');
      div.className = 'accordion' + (idx === 0 ? ' open' : '');
      div.dataset.category = cat;

      div.innerHTML =
        '<div class="accordion-header" role="button" tabindex="0" aria-expanded="' + (idx === 0) +
            '" aria-controls="acc-body-' + idx + '">' +
          '<span class="accordion-title">' + escapeHtml(cat) + '</span>' +
          '<div class="accordion-meta">' +
            '<span class="accordion-count">' + count + ' item' + (count !== 1 ? 's' : '') + '</span>' +
            '<div class="accordion-toggle">' + (idx === 0 ? '−' : '+') + '</div>' +
          '</div>' +
        '</div>' +
        '<div class="accordion-body" id="acc-body-' + idx + '">' + bodyHtml(products, '') + '</div>';

      var header = div.querySelector('.accordion-header');
      header.addEventListener('click', function () { toggleAccordion(div); });
      header.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggleAccordion(div);
        }
      });

      attachStepperEvents(div);
      container.appendChild(div);
    });
  }

  function toggleAccordion(div, forceOpen) {
    var shouldOpen = forceOpen !== undefined ? forceOpen : !div.classList.contains('open');
    div.classList.toggle('open', shouldOpen);
    div.querySelector('.accordion-toggle').textContent = shouldOpen ? '−' : '+';
    div.querySelector('.accordion-header').setAttribute('aria-expanded', shouldOpen);
  }

  // ── Steppers ───────────────────────────────────────────────────────────────

  function attachStepperEvents(container) {
    container.querySelectorAll('.stepper-btn').forEach(function (btn) {
      btn.addEventListener('click', handleStepperBtn);
    });
    container.querySelectorAll('.stepper-qty').forEach(function (inp) {
      inp.addEventListener('change', handleStepperInput);
      inp.addEventListener('input', handleStepperInput);
    });
  }

  function handleStepperBtn(e) {
    var btn = e.currentTarget;
    var id = btn.dataset.id;
    var row = qs('.stepper[data-id="' + id + '"]');
    if (!row) return;
    var current = parseInt(row.querySelector('.stepper-qty').value, 10) || 0;
    setQty(id, btn.dataset.action === 'inc' ? current + 1 : Math.max(0, current - 1));
  }

  function handleStepperInput(e) {
    setQty(e.currentTarget.dataset.id, parseInt(e.currentTarget.value, 10) || 0);
  }

  function setQty(id, qty) {
    qty = Math.max(0, Math.min(maxFor(id), qty));

    if (qty === 0) {
      delete order[id];
    } else {
      order[id] = qty;
    }

    // Search can render the same product more than once; keep every instance
    // of this id in step.
    qsa('.stepper[data-id="' + id + '"]').forEach(function (row) {
      row.querySelector('.stepper-qty').value = qty;
      row.querySelector('[data-action="dec"]').disabled = qty === 0;
      row.classList.toggle('has-qty', qty > 0);
    });

    updateOrderBar();
  }

  // ── Basket bar ─────────────────────────────────────────────────────────────

  function updateOrderBar() {
    var units = 0;
    var lines = 0;

    Object.keys(order).forEach(function (id) {
      if (order[id] > 0) {
        units += order[id];
        lines++;
      }
    });

    byId('bar-units').textContent = units;
    byId('bar-lines').textContent = lines;
    byId('btn-submit').disabled = lines === 0;
  }

  // ── Search ─────────────────────────────────────────────────────────────────

  var searchTimeout = null;

  byId('search-input').addEventListener('input', function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applySearch, 150);
  });

  byId('search-clear').addEventListener('click', function () {
    byId('search-input').value = '';
    applySearch();
  });

  function applySearch() {
    var raw = byId('search-input').value.trim();
    var q = raw.toLowerCase();

    byId('search-clear').classList.toggle('show', raw.length > 0);
    byId('search-wrap').classList.toggle('has-value', raw.length > 0);

    var anyVisible = false;

    qsa('.accordion').forEach(function (acc) {
      var products = productsIn(acc.dataset.category);
      var body = acc.querySelector('.accordion-body');

      if (!q) {
        body.innerHTML = bodyHtml(products, '');
        attachStepperEvents(body);
        acc.style.display = '';
        anyVisible = true;
        return;
      }

      var matched = products.filter(function (p) {
        return p.name.toLowerCase().indexOf(q) !== -1 ||
               p.sku.toLowerCase().indexOf(q) !== -1;
      });

      if (matched.length > 0) {
        body.innerHTML = bodyHtml(matched, raw);
        attachStepperEvents(body);
        toggleAccordion(acc, true);
        acc.style.display = '';
        anyVisible = true;
      } else {
        acc.style.display = 'none';
      }
    });

    byId('no-results').classList.toggle('show', !anyVisible && q.length > 0);
  }

  // ── Controls ───────────────────────────────────────────────────────────────

  byId('btn-expand-all').addEventListener('click', function () {
    qsa('.accordion').forEach(function (a) { toggleAccordion(a, true); });
  });

  byId('btn-collapse-all').addEventListener('click', function () {
    qsa('.accordion').forEach(function (a) { toggleAccordion(a, false); });
  });

  byId('btn-clear-order').addEventListener('click', function () {
    order = {};
    qsa('.stepper-qty').forEach(function (i) { i.value = 0; });
    qsa('.stepper').forEach(function (s) { s.classList.remove('has-qty'); });
    qsa('[data-action="dec"]').forEach(function (b) { b.disabled = true; });
    updateOrderBar();
  });

  // ── Submit ─────────────────────────────────────────────────────────────────

  var form = byId('mb-sof-form');
  var submitting = false;

  form.addEventListener('submit', function (e) {
    if (submitting) {
      e.preventDefault();
      return;
    }

    var ids = Object.keys(order).filter(function (id) { return order[id] > 0; });

    if (ids.length === 0) {
      e.preventDefault();
      return;
    }

    // The stepper inputs are UI only — search re-renders the accordion bodies
    // and would destroy the inputs for anything not matching, silently
    // shortening the order. Write the real lines from `order` instead.
    var lines = byId('mb-sof-lines');
    lines.innerHTML = '';

    ids.forEach(function (id) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'qty[' + id + ']';
      input.value = order[id];
      lines.appendChild(input);
    });

    submitting = true;
    var btn = byId('btn-submit');
    btn.disabled = true;
    btn.textContent = 'Adding to basket…';
  });

  // ── Init ───────────────────────────────────────────────────────────────────

  buildAccordions();
  updateOrderBar();
})();
