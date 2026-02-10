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

    $default_product = get_product_default_variation($product);
    $default_product_id = $default_product->get_id();
    $product_variations = $product->get_available_variations('objects');

    $args['samples'] = array_filter($product_variations, function ($variation) use ($default_product_id) {
        return $variation->get_id() !== $default_product_id;
    });

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

    $args['samples'] = array_map(function ($sample) {
        return [
            'product' => $sample,
        ];
    }, $args['samples']);

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
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
