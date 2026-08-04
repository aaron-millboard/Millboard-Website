<?php

namespace Granola\Components\ProductSamples;

\add_filter('granola/component/product-samples', __NAMESPACE__ . '\\filter_args');

// 4 args: the variation ID is needed, because WooCommerce passes the parent as
// $product_id and the parent is never itself a free sample.
\add_filter('woocommerce_add_to_cart_validation', __NAMESPACE__ . '\\sample_product_add_to_cart_validation', 10, 4);

// Sample buttons render inside the page body, so the script has to go in the
// footer to still be printed.
\add_filter('granola/partial/product-samples/enqueue_script_in_footer', '__return_true');

// Toggle a single sample in the basket without a page reload.
\add_action('wp_ajax_granola_sample_toggle', __NAMESPACE__ . '\\ajax_toggle_sample');
\add_action('wp_ajax_nopriv_granola_sample_toggle', __NAMESPACE__ . '\\ajax_toggle_sample');
