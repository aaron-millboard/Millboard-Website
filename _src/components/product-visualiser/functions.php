<?php

namespace Granola\Components\ProductVisualiser;

/**
 * The visualiser embed URL for this site, without a SKU.
 *
 * Set per site on the Product Settings options page, e.g.
 * https://example.com/embed?site=3. Empty means the visualiser is off, so
 * nothing renders. The product's SKU is appended per page.
 *
 * @return string
 */
function embed_base_url(): string
{
    if (!\function_exists('get_field')) {
        return '';
    }

    $url = \trim((string) \get_field('visualiser_embed_url', 'options'));

    return \wp_http_validate_url($url) ? $url : '';
}

/**
 * Whether the visualiser should show for a product.
 *
 * Needs the site option and a SKU, because the embed works per SKU. Use the
 * filter to narrow it to the ranges the visualiser supports.
 *
 * @param \WC_Product|null $product
 * @return bool
 */
function is_available(?\WC_Product $product = null): bool
{
    if (empty($product) || embed_base_url() === '') {
        return false;
    }

    return (bool) \apply_filters(
        'granola/product_visualiser/available',
        default_sku($product) !== '',
        $product
    );
}

/**
 * The SKU the visualiser opens with.
 *
 * Each colour is its own product, so the SKU sits on the product itself (the
 * last letter is the colour). A variation SKU is only a fallback.
 *
 * @param \WC_Product $product
 * @return string
 */
function default_sku(\WC_Product $product): string
{
    $sku = (string) $product->get_sku();

    if ($sku !== '' || !$product->is_type('variable')) {
        return $sku;
    }

    $id = \Theme\Utils\WooCommerce::get_default_variation_id($product);
    $variation = $id ? \wc_get_product($id) : null;

    return (string) ($variation ? $variation->get_sku() : '');
}

/**
 * Entry point markup. $variant is one of: button, swatch, tab.
 *
 * Every entry point carries data-visualiser-open, so the script needs no
 * knowledge of which one was clicked.
 *
 * @param string $variant
 * @return string
 */
function trigger(string $variant): string
{
    switch ($variant) {
        case 'swatch':
            return '<p class="product-visualiser__swatch-link"><a href="#visualiser" data-visualiser-open>'
                . \esc_html__('See this colour on your property', 'granola')
                . '</a></p>';

        case 'tab':
            return '<button type="button" class="product-visualiser__tab" data-visualiser-open>'
                . \esc_html__('See it on your property', 'granola')
                . '</button>';

        default:
            return '<button type="button" class="g-button product-visualiser__button" data-visualiser-open>'
                . '<span class="product-visualiser__button-text">'
                . '<span class="product-visualiser__button-title">' . \esc_html__('See it on your property', 'granola') . '</span>'
                . '<span class="product-visualiser__button-sub">' . \esc_html__('Upload a photo of your garden. Ready in about 30 seconds.', 'granola') . '</span>'
                . '</span>'
                . '<span class="product-visualiser__badge">' . \esc_html__('New', 'granola') . '</span>'
                . '</button>';
    }
}

function filter_args(array $args): ?array
{
    global $product;

    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [],
        'product' => $product,
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (!$args['product'] instanceof \WC_Product || !is_available($args['product'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Required classes and attributes.
    // -------------------------------------------------------------------------
    $args['classes'] = \array_merge(['product-visualiser'], $args['classes']);

    $base = embed_base_url();
    $parts = \wp_parse_url($base);
    $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '');

    $args['attributes'] = \array_merge([
        'data-embed-url' => $base,
        'data-embed-origin' => $origin,
        'data-sku' => default_sku($args['product']),
    ], $args['attributes']);

    return $args;
}
