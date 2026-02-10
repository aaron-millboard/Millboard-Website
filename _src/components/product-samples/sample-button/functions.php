<?php

namespace Granola\Components\ProductSamples\SampleButton;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'product' => null,
        'sample_type' => 'small',
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['product'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'g-button',
        'product-samples__button',
        'product-samples__button--' . $args['sample_type'],
    ], $args['classes']);

    $cart = WC()->cart;

    if (empty($cart)) {
        return null;
    }

    $product = \wc_get_product($args['product']);

    if (empty($product)) {
        return null;
    }

    $product_id = $product->get_id();
    $dimensions = $product->get_dimensions(false);
    $price = $product->get_price();
    $product_cart_id = $cart->generate_cart_id($product_id);
    $product_in_cart = $cart->find_product_in_cart($product_cart_id);

    // Generate a "remove from cart" url for small samples that are already in the cart.
    // if ($args['sample_type'] === 'small' && !empty($product_in_cart)) {
    //     $args['url'] = \wp_nonce_url(
    //         \add_query_arg([
    //             'remove_item' => $product_cart_id,
    //         ], \get_the_permalink()),
    //         'woocommerce-cart'
    //     );
    // } else {
        // Otherwise, just generate a simple "add to cart" url.
        $args['url'] = \add_query_arg([
            'add-to-cart' => $product_id,
        ], \get_the_permalink());
    // }

    $args['content'] = \Granola\Component::get('element', [
        'content' => sprintf(
            // translators: Sample type.
            \__('Add %s sample', 'granola'),
            $args['sample_type'],
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
    ]);

    // Clean up unnecessary args.
    unset($args['product']);

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
