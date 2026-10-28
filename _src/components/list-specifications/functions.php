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
    // NBS Source link for the product this page is about (UK products carry the field).
    // Shown once, under the heading of the first specifications block. Only a plain https URL is accepted.
    // Below it, the Lightbeans 3D texture button (a plain slug is accepted; the click handler is in wc-single-product).
    // -------------------------------------------------------------------------
    static $buttons_shown = false;

    if (!$buttons_shown && \is_singular('product')) {
        $product_id = \get_queried_object_id();
        $nbs_source_url = \get_field('nbs_source_url', $product_id);
        $lightbeans_slug = \get_field('lightbeans_slug', $product_id);

        if (is_string($nbs_source_url) && str_starts_with($nbs_source_url, 'https://')) {
            $args['nbs_source_url'] = \esc_url_raw($nbs_source_url);
            $buttons_shown = true;
        }

        if (is_string($lightbeans_slug) && preg_match('/^[a-z0-9-]+$/', $lightbeans_slug)) {
            $args['lightbeans_slug'] = $lightbeans_slug;
            $buttons_shown = true;
        }
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
