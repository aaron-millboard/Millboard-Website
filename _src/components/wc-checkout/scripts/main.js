 // Change position of WC AJAX notices
jQuery(document.body).on('checkout_error', function() {
    jQuery('.woocommerce-NoticeGroup-checkout').insertAfter('#checkout__notices');
});