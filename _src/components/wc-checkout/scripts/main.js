 // Change position of WC AJAX notices
jQuery(document.body).on('checkout_error', function() {
    jQuery('.woocommerce-NoticeGroup-checkout').insertAfter('#checkout__notices');
});

// Potential fix for Avalara x Address validaiton issues...
jQuery(document.body).one('update_checkout', function(){
   setTimeout(function(){
      jQuery(document.body).trigger('update_checkout');
   }, 500);
});
