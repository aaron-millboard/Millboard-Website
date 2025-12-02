<?php

namespace Granola\Components\ListSpecifications;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [],
        'preheading' => '',
        'heading' => '',
        'source' => 'manual',
        'specifications' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'list-specifications',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Fetch WooCommerce product attributes if needed
    // -------------------------------------------------------------------------
    if ($args['source'] === 'woocommerce' && is_plugin_active('woocommerce/woocommerce.php')) {
        $args['specifications'] = get_woocommerce_attributes();
    }

    // -------------------------------------------------------------------------
    // Bail early if no content
    // -------------------------------------------------------------------------
    if (empty($args['specifications'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Get WooCommerce product specifications
 *
 * @return array Array of specifications with 'name' and 'value' keys
 */
function get_woocommerce_attributes(): array
{
    global $product;

    // Bail if not on a product page
    if (!is_product() || !$product) {
        return [];
    }

    $specifications = [];

    // Get product attributes
    $attributes = $product->get_attributes();

    foreach ($attributes as $attribute) {
        $name = '';
        $value = '';

        if ($attribute->is_taxonomy()) {
            // Taxonomy-based attribute
            $name = wc_attribute_label($attribute->get_name());
            $terms = wp_get_post_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
            $value = $terms ? implode(', ', $terms) : '';
        } else {
            // Custom product attribute
            $name = $attribute->get_name();
            $value = implode(', ', $attribute->get_options());
        }

        if (!empty($name) && !empty($value)) {
            $specifications[] = [
                'name' => $name,
                'value' => $value,
            ];
        }
    }

    // Add SKU as last item
    $sku = $product->get_sku();
    if (!empty($sku)) {
        $specifications[] = [
            'name' => 'SKU',
            'value' => $sku,
        ];
    }

    return $specifications;
}
