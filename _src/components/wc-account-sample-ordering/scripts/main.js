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

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(ROOT).forEach(init);
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
