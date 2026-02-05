<?php

namespace Granola\Components\WC_SingleProduct\Gallery;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'post_thumbnail_id' => 0,
        'attachment_ids' => [],
    ], $args);

    // Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior.
    // This check protects against theme overrides being used on older versions of WC.
    if (!function_exists('wc_get_gallery_image_html')) {
        return null;
    }

    global $product;

    if (empty($product) || !$product instanceof \WC_Product) {
        return null;
    }

    $args['post_thumbnail_id'] = $product->get_image_id();
    $args['attachment_ids'] = $product->get_gallery_image_ids();

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
