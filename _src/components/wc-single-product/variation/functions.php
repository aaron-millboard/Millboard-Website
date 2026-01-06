<?php

namespace Granola\Components\WC_SingleProduct\Variation;

function filter_args(array $args): ?array
{

    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'terms' => [],
    ], $args);

    // ---------------------------------------
    // Set name if not set.
    // ---------------------------------------
    $args['name'] = 'attribute_' . sanitize_title($args['attribute']);

    // ---------------------------------------
    // Get terms
    // ---------------------------------------
    if (taxonomy_exists($args['attribute'])) {
        $args['terms'] = wc_get_product_terms(
            $args['product']->get_id(),
            $args['attribute'],
            array(
                'fields' => 'all',
            )
        );
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
