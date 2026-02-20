<?php

namespace Granola\Components\ProductCalculator\CTA;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'product' => wc_get_product(), // Default to global product if not provided.
        'classes' => [],
        'heading' => '',
    ], $args);

    if (empty($args['product']) || ! $args['product'] instanceof \WC_Product) {
        return null; // Return null if no valid product is provided.
    }

    $categories = wp_get_post_terms($args['product']->get_id(), 'product_cat');

    if (!is_wp_error($categories) && ! empty($categories)) {
        // Attempt to get yoast primary category
        if (function_exists('yoast_get_primary_term_id')) {
            $primary_category_id = yoast_get_primary_term_id('product_cat', $args['product']->get_id());
            $primary_category = get_term($primary_category_id);

            if (!is_wp_error($primary_category) && !empty($primary_category)) {
                $args['heading'] = $primary_category->name . ' Calculator';
            }
        } else {
            // Fallback to first category if no primary category set
            $args['heading'] = $categories[0]->name . ' Calculator';
        }
    }


    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
