<?php

namespace Granola\Components\ProductSamples;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'product_id' => \get_the_ID(),
        'samples' => [],
    ], $args);

    if (empty($args['product_id'])) {
        return null;
    }

    $product = \wc_get_product($args['product_id']);

    if (!($product instanceof \WC_Product_Variable)) {
        return null;
    }

    $args['samples'] = get_product_samples($product);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['samples'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'product-samples',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

function get_product_samples($wc_product)
{
    $default_product = get_product_default_variation($wc_product);

    // Bail early - no default set/found. Don't show any samples.
    if (empty($default_product)) {
        return [];
    }

    $default_product_id = $default_product->get_id();
    $product_variations = $wc_product->get_available_variations('objects');

    $samples = array_filter($product_variations, function ($variation) use ($default_product_id) {
        return $variation->get_id() !== $default_product_id;
    });

    return array_map(function ($sample) {
        return [
            'product' => $sample,
        ];
    }, $samples);
}

function get_product_default_variation($wc_product)
{
    $default_attributes = $wc_product->get_default_attributes();

    // ->find_matching_product_variation() needs term slugs of matching
    // attributes array to be prefixed with 'attribute_'
    $prefixed_slugs = array_map(function ($pa_name) {
        return 'attribute_' . $pa_name;
    }, array_keys($default_attributes));

    $default_attributes = array_combine($prefixed_slugs, $default_attributes);
    $default_variation_id = ( new \WC_Product_Data_Store_CPT() )->find_matching_product_variation($wc_product, $default_attributes);

    return \wc_get_product($default_variation_id);
}

/**
 * Determine whether a sample product should be added to the basket.
 *
 * @param bool $passed
 * @param integer $product_id Product ID being validated.
 * @param integer $quantity Quantity added to the cart.
 * @return bool True if the item passed validation.
 */
function sample_product_add_to_cart_validation(bool $add_to_cart, int $product_id, int $qty): bool
{
    $product = \wc_get_product($product_id);

    // Bail early - not a product variation (different to a "variable" product), no samples.
    if (!\Theme\WooCommerce\Utils::is_free_sample($product)) {
        return $add_to_cart;
    }

    // Count the number of samples in the cart.
    $sample_count = get_cart_sample_count();

    if ($sample_count + $qty > 3) {
        \wc_add_notice(
            \__('You can only add a maximum of 3 free samples', 'granola'),
            'error'
        );
        $add_to_cart = false;
    }

    return $add_to_cart;
}

/**
 * Count the number of free "sample" products in the cart.
 *
 * A sample product is a variation product that isn't the default variation and has a price of 0.
 *
 * @return integer The number of free "sample" products in the cart.
 */
function get_cart_sample_count(): int
{
    $cart = WC()->cart->get_cart();

    // Count the number of samples in the cart.
    return array_reduce($cart, function ($samples_quantity, $cart_item) {
        // Bail early - no product/variation id found.
        if (empty($cart_item['product_id']) || empty($cart_item['variation_id'])) {
            return $samples_quantity;
        }

        $card_product_obj = \wc_get_product($cart_item['variation_id']);

        // Bail early - this is a default product variation (i.e. not a sample).
        if (\Theme\WooCommerce\Utils::is_default_product($card_product_obj)) {
            return $samples_quantity;
        }

        // Bail early - sample isn't free.
        if (isset($cart_item['line_total']) && $cart_item['line_total'] > 0) {
            return $samples_quantity;
        }

        // Carry the quantity of samples.
        return $samples_quantity + (int) $cart_item['quantity'];
    }, 0);
}
