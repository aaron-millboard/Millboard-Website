<?php

namespace Granola\Components\WC_Globals;

add_action("init", function () {
    // Declare WooCommerce support
    add_theme_support('woocommerce');
});

// Disable WooCommerce default styles
add_filter('woocommerce_enqueue_styles', '__return_empty_array');


// chgange wc thumbnail size in single product gallery
add_filter('woocommerce_gallery_thumbnail_size', function ($size) {
    return 'large';
});

// change wc image size in single product gallery
add_filter('woocommerce_gallery_image_size', function ($size) {
    return 'full';
});

// Cleanup single product summary. We can't remove it as it also generates structured data and loads some JS.
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);

remove_action('woocommerce_single_variation', 'woocommerce_single_variation', 10); // removes variation data
