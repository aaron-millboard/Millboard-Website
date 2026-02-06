<?php

namespace Granola\Components\WC_Globals;

// Declare WooCommerce support.
\add_theme_support('woocommerce');

// Remove lightbox functionality.
\remove_theme_support('wc-product-gallery-zoom');
\remove_theme_support('wc-product-gallery-lightbox');
\remove_theme_support('wc-product-gallery-slider');

// Disable WooCommerce default styles.
\add_filter('woocommerce_enqueue_styles', '__return_empty_array');

// Change WC thumbnail size in single product gallery.
\add_filter('woocommerce_gallery_thumbnail_size', function ($size) {
    return 'large';
});

// Change WC image size in single product gallery.
\add_filter('woocommerce_gallery_image_size', function ($size) {
    return 'full';
});

// Remove variation data
\remove_action('woocommerce_single_variation', 'woocommerce_single_variation', 10);
