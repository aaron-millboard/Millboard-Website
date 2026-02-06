<?php

namespace Granola\Components\WC_SingleProduct;

function filter_args(array $args): ?array
{
    global $product;

    if (empty($product) || ! $product instanceof \WC_Product) {
        return [];
    }

    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'id' => 'product',
        'classes' => [],
        'preheading' => '',
        'heading' => '',
        'description' => '',
        'header_cta' => [],
        'colour_variations' => [],
        'width_variations' => [],
        'board_width_variations' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // 1. Required classes and ID
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'wp-block',
        'woocommerce',
    ], \wc_get_product_class('', $product));

    $args['id'] = "product-{$product->get_id()}";

    // -------------------------------------------------------------------------
    // 2. Retrieve product data
    // -------------------------------------------------------------------------

    // Preheading - get name of first category
    $categories = \wp_get_post_terms($product->get_id(), 'product_cat');
    if (!is_wp_error($categories) && ! empty($categories)) {
        $args['preheading'] = $categories[0]->name;
    }

    // Heading - get product title
    $args['heading'] = $product->get_name();

    // Description - get product short description
    $args['description'] = $product->get_short_description();

    // CTA under description
    $args['header_cta'] = \get_field('header_cta', $product->get_id());
    $args['header_cta']['content'] = \Granola\SVG::get('icons-custom/pencil.svg') . $args['header_cta']['title'];

    // -------------------------------------------------------------------------
    // 3. Variations (to be used in the variation selector component)
    // -------------------------------------------------------------------------

    $protected_variations = ['colour', 'board_width', 'width'];
    $args['selectors'] = [];
    foreach ($protected_variations as $protected_variation) {
        $repeater_data = $args[$protected_variation . '_variations'];
        // Skip if corresponding repeater is empty
        if (empty($repeater_data)) {
            continue;
        }

        // get attribute label
        $label = wc_attribute_label('pa_' . $protected_variation);
        // Try to repeat with hyphen if slug returned
        if ($label === $protected_variation) {
            $label = wc_attribute_label('pa_' . str_replace('_', '-', $protected_variation));
        }

        $args['selectors'][] = [
            'variation' => $protected_variation,
            'heading' => sprintf(__('Select %s:', 'granola'), strtolower($label)),
            'variations' => $repeater_data,
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

// Add radio buttons for variations instead of dropdowns
function render_radio_variations($html, $args)
{
    if (!$args['options'] || !$args['product']) {
        return $html;
    }

    $html .= \Granola\Component::get('wc-single-product/variation', $args);

    return $html;
}
