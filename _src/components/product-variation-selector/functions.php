<?php

namespace Granola\Components\ProductVariationSelector;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'heading' => null,
        'variation' => null,
        'variants' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'product-variation-selector',
    ], $args['classes']);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['variation'])) {
        return null;
    }

    $product = \wc_get_product(\get_the_ID());
    if (empty($product)) {
        return null;
    }

    $variation = $args['variation'];
    $variants = $args['variations'];

    if (empty($variants)) {
        return null;
    }

    foreach ($variants as $variant) {
        $is_current = \has_term(
            $variant[$variation]->term_id,
            'pa_' . $variation,
        );

        $image = \get_field('image', $variant[$variation]);

        $args['variants'][] = [
            'content' => $variant[$variation]->name . \Granola\Component::get('image', [
                'attachment_id' => $image['attachment_id'] ?? 0,
                'size' => 'thumbnail',
                'classes' => [
                    'product-variation-selector__image',
                ],
            ]),
            'url' => $is_current ? '' : \get_permalink($variant['product']->ID),
            'classes' => [
                'product-variation-selector__link',
                'product-variation-selector__link--' . $variation,
                $is_current ? 'product-variation-selector__link--current' : null,
            ],
            'attributes' => [
                'data-text' => $variant[$variation]->name,
            ],
        ];

        if ($variation === 'board_width' && \has_term($variant[$variation]->name, 'product_tag')) {
            $args['variants'][count($args['variants']) - 1]['classes'][] = 'product-variation-selector__link--current';
        }
    }

    if (!empty($args['heading'])) {
        $args['heading'] = [
            'content' => $args['heading'],
            'classes' => [
                'product-variation-selector__heading',
                'is-style-typestyle-h6',
            ],
        ];
    }

    $args['classes'][] = 'product-variation-selector--' . $args['variation'];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
