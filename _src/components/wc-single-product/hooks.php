<?php

namespace Granola\Components\WC_SingleProduct;

\add_filter('granola/component/wc-single-product', __NAMESPACE__ . '\\filter_args');

/* Remvove WooCommerce default gallery features */
//add_filter('woocommerce_single_product_zoom_enabled', '__return_false');
//add_filter('woocommerce_single_product_photoswipe_enabled', '__return_false');


// Add radio buttons for variations instead of dropdowns
add_filter('woocommerce_dropdown_variation_attribute_options_html', __NAMESPACE__ . '\\render_radio_variations', 20, 2);
function render_radio_variations($html, $args)
{

    if (!$args['options'] || !$args['product']) {
        return $html;
    }

    $html .= \Granola\Component::get('wc-single-product/variation', $args);

    return $html;
}
