 // Change position of WC AJAX notices
jQuery(document.body).on('checkout_error', function() {
    jQuery('.woocommerce-NoticeGroup-checkout').insertAfter('#checkout__notices');
});

jQuery(document.body).on('click', 'a.woocommerce-terms-and-conditions-link', function(event) {
   event.preventDefault();
   window.open(jQuery(this).attr('href'), '_blank', 'noopener');
});

// Potential fix for Avalara x Address validaiton issues...
jQuery(document.body).one('update_checkout', function(){
   setTimeout(function(){
      jQuery(document.body).trigger('update_checkout');
   }, 500);
});

// Recalculate shipping when the "who am I" field changes so homeowner-specific
// rates are applied or removed without requiring an address field interaction.
jQuery(document.body).on('change', '#who-am-i, #billing_who-am-i, [name="who-am-i"], [name="who-am-i?"]', function() {
   jQuery(document.body).trigger('update_checkout');
});

// ---------------------------------------------------------------------------
// Audience-dependent marketing permission (see Theme\WooCommerce\ConsentFields).
//
// France cannot be called as a consumer without prior opt-in consent, but can be
// called as a business on legitimate interest, so the question shown depends on the
// "Who am I?" answer. Which options count as a consumer comes from the server via
// data-mb-consumer-values, so this file never has to know the option labels.
//
// This is presentation only. The server discards whichever branch does not apply,
// so if this script fails the customer sees both questions and still gets the right
// basis recorded.
// ---------------------------------------------------------------------------
(function ($) {
   var TRIGGER = '.mb-consent-trigger, #who-am-i, [name="who-am-i"]';

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
      // Hidden with an inline style rather than a class, deliberately. Perfmatters
      // Remove Unused CSS deletes rules for classes that only ever appear via JS, so
      // a `.mb-consent--hidden { display: none }` rule would be stripped on the very
      // page it is needed. An inline style cannot be. The class is still toggled for
      // anything that wants to hook onto it, but nothing depends on it.
      $row.toggleClass('mb-consent--hidden', !visible);
      $row.attr('hidden', visible ? null : 'hidden');

      if (visible) {
         $row.show();
      } else {
         $row.hide();
      }

      // Disabled inputs are left out of the submitted form, so a row the customer
      // never saw cannot contribute a value.
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
         return; // Server did not mark any option as a consumer; leave everything shown.
      }

      var isConsumer = values.indexOf($trigger.val()) !== -1;
      var answered = ($trigger.val() || '') !== '';

      setRowState($('.mb-consent--consumer'), answered && isConsumer);
      setRowState($('.mb-consent--business'), answered && !isConsumer);

      // The consumer question is a required yes/no now, so mirror that onto its
      // radios. Native constraint validation then catches an unanswered pair before
      // the request is made, which is nicer than a round trip to the top of the page.
      //
      // Taken off the hidden ones as well as disabled. Disabled inputs are already
      // exempt, but a required hidden input is the classic way to make a form refuse
      // to submit with a validation bubble nobody can see, so both, not either.
      // ConsentFields::validate() enforces it server side regardless.
      $('.mb-consent--consumer')
         .find('input[type="radio"]')
         .prop('required', answered && isConsumer);
   }

   $(function () {
      apply();
      $(document.body).on('change', TRIGGER, apply);
      // Billing fields are not replaced by update_checkout, but re-running is cheap
      // and covers any future fragment that does replace them.
      $(document.body).on('updated_checkout', apply);
   });
})(jQuery);
