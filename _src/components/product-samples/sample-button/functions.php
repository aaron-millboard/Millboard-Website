<?php

namespace Granola\Components\ProductSamples\SampleButton;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'attributes' => [],
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

    // Samples are toggled over AJAX so that choosing three of them doesn't cost
    // the visitor three full page reloads. The href below is kept as a no-JS
    // fallback.
    \Granola\Components\ProductSamples\enqueue_assets();

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

    // Hooks for the AJAX toggle. The button identifies itself and its current
    // state so the script can flip it without a page load. Only added when the
    // feature is on, so the markup is unchanged for sites still opted out.
    $ajax_basket = \Granola\Components\ProductSamples\ajax_sample_basket_enabled();

    // The three pieces of the addable label. Held separately so they can also be
    // handed to the script, which needs to rebuild this state after a removal -
    // including for buttons that were already in the basket on page load.
    $add_label = !empty($sample_size) ? sprintf(
        // translators: Sample type.
        \__('Add %s sample', 'granola'),
        strtolower($sample_size),
    ) : \__('Add sample', 'granola');

    $add_dimensions = sprintf(
        // translators: 1: Product length. 2: Product width.
        \__('%1$smm x %2$smm', 'granola'),
        $dimensions['length'],
        $dimensions['width'],
    );

    $add_price = !empty($price) ? \get_woocommerce_currency_symbol() . $price : \__('Free', 'granola');

    if ($ajax_basket) {
        $args['attributes']['data-sample-product-id'] = $product_id;
        $args['attributes']['data-sample-action'] = !empty($product_cart_id) ? 'remove' : 'add';
        $args['attributes']['data-sample-label'] = $add_label;
        $args['attributes']['data-sample-dimensions'] = $add_dimensions;
        $args['attributes']['data-sample-price'] = $add_price;
        // Carried so the analytics event can name the sample. The AJAX add
        // produces no ?add-to-cart= page view, so this attribute is the only
        // place the client learns what was chosen.
        $args['attributes']['data-sample-name'] = $product->get_name();
    }

    // Generate a "remove from cart" url for small samples that are already in the cart.
    if (!empty($product_cart_id) && !\Theme\WooCommerce\Utils::is_default_product($product)) {
        if ($ajax_basket) {
            $args['attributes']['data-sample-cart-item'] = $product_cart_id;
        }

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

        // Built from the same three values handed to the script above, so the
        // visible label and the state it restores after a removal cannot drift.
        $args['content'] = \Granola\Component::get('element', [
            'content' => $add_label,
            'classes' => [
                'product-samples__button__content',
            ],
        ]) . \Granola\Component::get('element', [
            'content' => $add_dimensions,
            'classes' => [
                'product-samples__button__dimensions',
            ],
        ]) . \Granola\Component::get('element', [
            'content' => $add_price,
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
