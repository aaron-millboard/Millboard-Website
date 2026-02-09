<?php

namespace Granola\Components\ProductSamples;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'samples' => [],
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (!\is_singular('product')) {
        return null;
    }

    if (empty($args['samples'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'product-samples',
    ], $args['classes']);

    $cart = WC()->cart;

    $args['samples'] = array_map(function ($sample) use ($cart) {
        $product = $sample['product'] ?? null;

        if (empty($product)) {
            return null;
        }

        $product = \wc_get_product($product);
        $product_id = $product->get_id();
        $dimensions = $product->get_dimensions(false);
        $price = $product->get_price();
        $product_cart_id = $cart->generate_cart_id($product_id);

        // Generate a "remove from cart" url for small samples that are already in the cart.
        if ($sample['sample_type'] === 'small' && !empty($product_cart_id)) {
            $url = \wc_get_cart_remove_url($product_cart_id);
        } else {
            // Otherwise, just generate a simple "add to cart" url.
            $url = \add_query_arg([
                'add-to-cart' => $product_id,
            ], \get_the_permalink());
        }

        $button_args = [
            'url' => $url,
            'content' => \Granola\Component::get('element', [
                'content' => sprintf(
                    // translators: Sample type.
                    \__('Add %s sample', 'granola'),
                    $sample['sample_type'],
                ),
                'classes' => [
                    'product-samples__button__content',
                ],
            ]) . \Granola\Component::get('element', [
                'content' => sprintf(
                    // translators: 1: Product length. 2: Product width.
                    \__('%1$smm x %2$smm', 'granola'),
                    $dimensions['length'],
                    $dimensions['width'],
                ),
                'classes' => [
                    'product-samples__button__dimensions',
                ],
            ]) . \Granola\Component::get('element', [
                'content' => !empty($price) ? \get_woocommerce_currency_symbol() . $price : \__('Free', 'granola'),
                'classes' => [
                    'product-samples__button__price',
                ],
            ]),
            'classes' => [
                'g-button',
                'product-samples__button',
                'product-samples__button--' . $sample['sample_type'],
            ],
        ];

        return $button_args;
    }, $args['samples']);

    // Remove empty sample data.
    $args['samples'] = array_filter($args['samples']);

    if (empty($args['samples'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
