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
