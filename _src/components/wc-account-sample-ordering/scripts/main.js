/**
 * Sample ordering -- show "Clear order" only once there is an order.
 *
 * The widget keeps its quantities in a JavaScript object of its own and
 * publishes the running totals into the basket bar. Rather than reach into
 * that object, or edit the widget's file, this watches the line counter it
 * already writes and toggles a class on the container. The stylesheet does the
 * rest.
 *
 * Watching the counter rather than binding to the steppers matters: the
 * accordion bodies are re-rendered on every search, which destroys and
 * recreates every stepper, so any listener bound to them would be lost. The
 * counter element survives.
 */

const ROOT = '.mb-sof';
const COUNTER = '#bar-lines';
const READY_CLASS = 'has-order';

function sync(root, counter) {
    const lines = parseInt(counter.textContent, 10);

    root.classList.toggle(READY_CLASS, Number.isFinite(lines) && lines > 0);
}

function init(root) {
    const counter = root.querySelector(COUNTER);

    if (!counter) {
        return;
    }

    sync(root, counter);

    // characterData with subtree: the widget replaces the text node inside the
    // <strong>, which childList alone on the element would not always report.
    const observer = new MutationObserver(() => sync(root, counter));

    observer.observe(counter, {
        childList: true,
        characterData: true,
        subtree: true,
    });
}

/**
 * A rep can collapse every section and still see where their order is.
 *
 * The widget only ever shows a section's total item count, so once the
 * accordions are shut there is nothing to say which of the twenty-one holds
 * the four boards already chosen. This writes an "n selected" tag beside that
 * count.
 *
 * Bound to the ROOT, never to a stepper. The widget re-renders every accordion
 * body on each keystroke of its search, so a listener on a stepper would be
 * destroyed with it; the root survives and delegation still reaches them. The
 * observer is for that re-render, the input and click listeners are for value
 * changes, which mutate no attribute and so raise no mutation at all.
 */
const SELECTED_CLASS = 'mb-sof-selected';

function tagSelected(root, template) {
    root.querySelectorAll('.accordion').forEach((accordion) => {
        let total = 0;

        accordion.querySelectorAll('.stepper-qty').forEach((input) => {
            const qty = parseInt(input.value, 10);

            if (Number.isFinite(qty) && qty > 0) {
                total += qty;
            }
        });

        const meta = accordion.querySelector('.accordion-meta');

        if (!meta) {
            return;
        }

        let tag = meta.querySelector(`.${SELECTED_CLASS}`);

        if (!total) {
            if (tag) {
                tag.remove();
            }

            return;
        }

        if (!tag) {
            tag = document.createElement('span');
            tag.className = SELECTED_CLASS;
            // Before the toggle, so it reads "22 items  4 selected  +".
            meta.insertBefore(tag, meta.firstChild);
        }

        const next = template.replace('%d', String(total));

        // ⚠️ Only write when it actually changes. Assigning textContent always
        // replaces the child text node, which the observer below sees as a
        // mutation, which calls this again: an unconditional write here is an
        // infinite loop that pegs a core for as long as the tab is open.
        if (tag.textContent !== next) {
            tag.textContent = next;
        }
    });
}

/**
 * Say why Continue is disabled, rather than leaving a dead button.
 */
function placeEmptyNote(root, text) {
    const bar = root.querySelector('.order-bar');

    if (!bar || !text || bar.querySelector('.mb-sof-note')) {
        return;
    }

    const note = document.createElement('span');

    note.className = 'mb-sof-note';
    note.textContent = text;
    bar.appendChild(note);
}

function initExtras(widget) {
    const root = widget.querySelector(ROOT);

    if (!root) {
        return;
    }

    const selectedTemplate = widget.dataset.selectedNote || '%d selected';
    const recount = () => tagSelected(root, selectedTemplate);

    placeEmptyNote(root, widget.dataset.emptyNote);
    recount();

    root.addEventListener('input', recount);
    root.addEventListener('click', recount);

    new MutationObserver(() => {
        placeEmptyNote(root, widget.dataset.emptyNote);
        recount();
    }).observe(root, { childList: true, subtree: true });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(ROOT).forEach(init);
    document.querySelectorAll('.mb-account-samples__widget').forEach(initExtras);
});

// ---------------------------------------------------------------------------
// Audience-dependent consent rows on the sample-order form.
//
// A MIRROR of the block at the bottom of components/wc-checkout/scripts/main.js,
// and deliberately a copy rather than a shared module: that file also fires
// `update_checkout` and rewrites checkout notices, neither of which may run on
// My Account, so it cannot simply be enqueued here. Extracting the shared half
// means editing the live checkout bundle, which is not a trade worth making for
// a screen Compliance has not approved yet. If this form ships, unify the two.
//
// Presentation only. ConsentFields::record_permission() branches on the audience
// server side and reads only the field that applies, so if this never runs the
// rep sees both questions and the right basis is still recorded.
// ---------------------------------------------------------------------------
(function ($) {
   var TRIGGER = '#who-am-i, [name="who-am-i"]';

   function consumerValues($trigger) {
      var raw = $trigger.attr('data-mb-consumer-values');

      if (!raw) {
         return [];
      }

      try {
         var parsed = JSON.parse(raw);
         return Array.isArray(parsed) ? parsed : [];
      } catch (e) {
         return [];
      }
   }

   function setRowState($row, visible) {
      // Inline style, not a class: Perfmatters Remove Unused CSS strips rules for
      // classes that only ever appear via JS, so a `.mb-consent--hidden` rule would
      // be deleted on the very page that needs it.
      $row.toggleClass('mb-consent--hidden', !visible);
      $row.attr('hidden', visible ? null : 'hidden');

      if (visible) {
         $row.show();
      } else {
         $row.hide();
      }

      // A row the rep never saw must not contribute a value.
      $row.find('input, select, textarea').each(function () {
         var $input = $(this);

         $input.prop('disabled', !visible);

         if (!visible) {
            if ($input.is(':checkbox, :radio')) {
               $input.prop('checked', false);
            } else {
               $input.val('');
            }
         }
      });
   }

   function apply() {
      var $trigger = $(TRIGGER).filter('select').first();

      if (!$trigger.length) {
         return;
      }

      var values = consumerValues($trigger);

      if (!values.length) {
         return; // Nothing marked as a consumer; leave everything visible.
      }

      var isConsumer = values.indexOf($trigger.val()) !== -1;
      var answered = ($trigger.val() || '') !== '';

      setRowState($('.mb-consent--consumer'), answered && isConsumer);
      setRowState($('.mb-consent--business'), answered && !isConsumer);

      $('.mb-consent--consumer')
         .find('input[type="radio"]')
         .prop('required', answered && isConsumer);
   }

   $(function () {
      if (!$('.mb-sof-details__consent').length) {
         return; // Not the sample-order details step.
      }

      apply();
      $(document.body).on('change', TRIGGER, apply);
   });
})(jQuery);
