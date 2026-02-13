<?php

namespace Granola\Components\ProductLoop;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'items' => [],
        'object' => \Granola\WordPress\PageObject::get(),
        'content' => 'automatic',
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'product-loop',
        'wp-block',
    ], $args['classes']);

    if ($args['content'] === 'automatic') {
        $query_args = [
            'post_type' => 'product',
            'post_per_page' => 500,
        ];

        if (!empty($args['product_category'])) {
            foreach ($args['product_category'] as $term) {
                $query_args['tax_query'][] = [
                    [
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $term,
                    ],
                ];
            }

            if (count($args['product_category']) > 1) {
                $query_args['tax_query'][]['relation'] = 'AND';
            }
        } else {
            // $args['object']
        }


        $product_query = new \WP_Query($query_args);

        // \Granola\Debug::dump($product_query->have_posts());
        if ($product_query->have_posts()) {
            foreach ($product_query->posts as $product) {
                $args['items'][]['object'] = $product;
            }
        }
    } elseif (!empty($args['products'])) {
        foreach ($args['products'] as $product) {
            $args['items'][]['object'] = $product;
        }
    }

    // \Granola\Debug::dump($args);

    // \Granola\Debug::dump($args['items']);

    $args['cards_args'] = [
        'items' => $args['items'],
        'wp_query' => false,
        'post_type' => $args['post_type'] ?? \get_post_type(),
        'limit' => 500, // arbitrary large number.
        'columns' => 4,
    ];

    if (!empty($args['heading'])) {
        $args['heading'] = [
            'content' => $args['heading'],
            'classes' => ['product-loop__heading'],
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
