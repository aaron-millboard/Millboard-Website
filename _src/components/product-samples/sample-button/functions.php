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
    ], $args['classes']);

    $cart = WC()->cart;

    if (empty($cart)) {
        return null;
    }

    $product = \wc_get_product($args['product']);

    if (empty($product)) {
        return null;
    }

    if ($product->get_stock_status() !== 'instock') {
        return null;
    }

    $product_id = $product->get_id();
    $dimensions = $product->get_dimensions(false);
    $price = $product->get_price();

    $sample_size_attribute_name = \get_field('product_sample_size_taxonomy', 'options');
    $sample_size = $product->get_attribute($sample_size_attribute_name ?? 'pa_sample-size');

    // Check if product is in cart - works for both simple and variable products
    $cart_items = $cart->get_cart();
    $product_cart_id = false;
    $sample_count = 1;
    foreach ($cart_items as $cart_item_key => $cart_item) {
        $cart_product_obj = \wc_get_product($cart_item['variation_id'] ?? $cart_item['product_id']);

        if (!empty($cart_product_obj) && \Theme\WooCommerce\Utils::is_free_sample($cart_product_obj)) {
            if ($cart_item['product_id'] === $product_id || $cart_item['variation_id'] === $product_id) {
                $product_cart_id = $cart_item_key;
                break;
            } else {
                $sample_count++; // count what position this sample is in the basket.
            }
        }
    }

    if (!empty($sample_size)) {
        $args['classes'][] = 'product-samples__button--' . strtolower($sample_size);
    }

    if (!empty($product_cart_id)) {
        $args['classes'][] = 'product-samples__button--in-cart';
    }

    // Generate a "remove from cart" url for small samples that are already in the cart.
    if (!empty($product_cart_id) && !\Theme\WooCommerce\Utils::is_default_product($product)) {
        $args['url'] = \wp_nonce_url(
            \add_query_arg([
                'remove_item' => $product_cart_id,
            ], \get_the_permalink()),
            'woocommerce-cart'
        );

        $args['content'] = \Granola\Component::get('element', [
            'content' => \__('Added to basket', 'granola'),
            'classes' => [
                'product-samples__button__content',
                'product-samples__button__content--added',
            ],
        ]) . \Granola\Component::get('element', [
            'content' => sprintf(
                // translators: 1: HTML opening tag. 2: Product place in basket. 3: HTML closing tag.
                \__('Remove %1$s%2$s/3%3$s', 'granola'),
                '<strong>',
                $sample_count,
                '</strong>',
            ),
            'classes' => [
                'product-samples__button__action',
            ],
        ]);
    } else {
        // Otherwise, just generate a simple "add to cart" url.
        $args['url'] = \add_query_arg([
            'add-to-cart' => $product_id,
        ], '');

        $args['content'] = \Granola\Component::get('element', [
            'content' => !empty($sample_size) ? sprintf(
                // translators: Sample type.
                \__('Add %s sample', 'granola'),
                strtolower($sample_size),
            ) : \__('Add sample', 'granola'),
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
    }


    // Clean up unnecessary args.
    unset($args['product']);

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
