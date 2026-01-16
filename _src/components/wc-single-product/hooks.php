<?php

namespace Granola\Components\WC_SingleProduct;

\add_filter('granola/component/wc-single-product', __NAMESPACE__ . '\\filter_args');

/* Remvove WooCommerce default gallery features */
//add_filter('woocommerce_single_product_zoom_enabled', '__return_false');
//add_filter('woocommerce_single_product_photoswipe_enabled', '__return_false');

// Cleanup single product summary. We can't remove it as it also generates structured data and loads some JS.
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);

// Add radio buttons for variations instead of dropdowns
add_filter('woocommerce_dropdown_variation_attribute_options_html', __NAMESPACE__ . '\\render_radio_variations', 20, 2);
