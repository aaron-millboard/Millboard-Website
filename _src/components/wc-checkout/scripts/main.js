 // Change position of WC AJAX notices
jQuery(document.body).on('checkout_error', function() {
    // Reposition the error notices and expose them as an alert so screen
    // readers announce checkout validation errors (WCAG 4.1.3 Status Messages).
    // #checkout__notices is display:none (a positioning anchor only), so the
    // live-region role must go on the visible notice group itself.
    jQuery('.woocommerce-NoticeGroup-checkout')
        .attr('role', 'alert')
        .insertAfter('#checkout__notices');
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
