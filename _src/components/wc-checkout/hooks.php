<?php

namespace Granola\Components\WC_Checkout;

\add_filter('wc_avatax_enable_address_validation', '__return_false');

// Allow shipping section to be updated as part of WC's 'update_order_review' ajax call.
\add_filter('woocommerce_update_order_review_fragments', __NAMESPACE__ . '\\update_shipping_methods_fragment');
