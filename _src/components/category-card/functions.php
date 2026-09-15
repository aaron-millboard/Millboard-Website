<?php

namespace Granola\Components\CategoryCard;

/**
 * One product card on a shop category page.
 *
 * A separate component rather than a change to `card`. That one renders
 * through `media-object` and is shared with cards-automatic, which is on 120
 * published pages on en-gb alone, so it cannot be redesigned for this grid.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'object' => null,
        'product_id' => 0,
        'sample_url' => '',
        'flag' => '',
    ], $args);

    // -------------------------------------------------------------------------
    // Resolve the product.
    // -------------------------------------------------------------------------
    if (empty($args['product_id']) && $args['object'] instanceof \WP_Post) {
        $args['product_id'] = $args['object']->ID;
    }

    $product_id = (int) $args['product_id'];

    if ($product_id < 1) {
        return null;
    }

    $args['classes'] = array_merge([
        'category-card',
    ], $args['classes']);

    $args['name'] = \get_the_title($product_id);
    $args['url'] = (string) \get_permalink($product_id);
    $args['image_id'] = (int) \get_post_thumbnail_id($product_id);
    $args['range'] = get_range_label($product_id);
    $args['spec'] = get_spec_line($product_id);
    $args['price'] = get_board_price($product_id);

    // -------------------------------------------------------------------------
    // Bail early if the product has no title to show.
    // -------------------------------------------------------------------------
    if (empty($args['name'])) {
        return null;
    }

    // Sorting and filtering happen on the rendered cards, so the values the
    // grid needs travel on the card itself rather than in a second query.
    $args['sort_price'] = get_board_price_value($product_id);
    $args['filters'] = get_filter_values($product_id);

    return $args;
}

/**
 * The range a product belongs to, for the card eyebrow.
 *
 * The range is the category one level below the top, so a board reads
 * "Enhanced Grain". Not the deepest category, which would read "176mm": the
 * widths sit beneath the range as categories of their own.
 *
 * @param int $product_id The product.
 * @return string The label.
 */
function get_range_label(int $product_id): string
{
    $terms = \get_the_terms($product_id, 'product_cat');

    if (empty($terms) || \is_wp_error($terms)) {
        return '';
    }

    $shallowest = null;
    $shallowest_depth = PHP_INT_MAX;

    foreach ($terms as $term) {
        $depth = count(\get_ancestors($term->term_id, 'product_cat', 'taxonomy'));

        // Depth 1 is the range. Anything deeper is a width or a sub-type.
        if ($depth === 1) {
            return $term->name;
        }

        if ($depth > 0 && $depth < $shallowest_depth) {
            $shallowest = $term;
            $shallowest_depth = $depth;
        }
    }

    return $shallowest ? $shallowest->name : '';
}

/**
 * The short specification line under the name, for example "176mm board".
 *
 * @param int $product_id The product.
 * @return string The line.
 */
function get_spec_line(int $product_id): string
{
    $product = \function_exists('wc_get_product') ? \wc_get_product($product_id) : null;

    if (empty($product)) {
        return '';
    }

    $width = $product->get_attribute('pa_board-width');

    return $width !== '' ? $width : '';
}

/**
 * The price of the board itself, formatted.
 *
 * @param int $product_id The product.
 * @return string The formatted price, or an empty string.
 */
function get_board_price(int $product_id): string
{
    $value = get_board_price_value($product_id);

    if ($value === null || !\function_exists('wc_price')) {
        return '';
    }

    return \wc_price($value);
}

/**
 * The numeric price of the board itself.
 *
 * A board is a variable product whose variations are the board plus its two
 * samples, on pa_sample-size as full, large and small. The small sample is
 * free, so anything that just takes the minimum shows nothing but zero. Only
 * the "full" variation is the board.
 *
 * @param int $product_id The product.
 * @return float|null The price, or null if there is none.
 */
function get_board_price_value(int $product_id): ?float
{
    $product = \function_exists('wc_get_product') ? \wc_get_product($product_id) : null;

    if (empty($product)) {
        return null;
    }

    if (!$product->is_type('variable')) {
        $price = (float) $product->get_price();

        return $price > 0 ? $price : null;
    }

    $lowest = null;

    foreach ($product->get_children() as $variation_id) {
        $variation = \wc_get_product($variation_id);

        if (empty($variation)) {
            continue;
        }

        if (strtolower((string) $variation->get_attribute('pa_sample-size')) !== 'full') {
            continue;
        }

        $price = (float) $variation->get_price();

        if ($price > 0 && ($lowest === null || $price < $lowest)) {
            $lowest = $price;
        }
    }

    return $lowest;
}

/**
 * The values the grid filters on, as slugs.
 *
 * @param int $product_id The product.
 * @return array<string,array<string>> Keyed by filter group.
 */
function get_filter_values(int $product_id): array
{
    $filters = [];

    $terms = \get_the_terms($product_id, 'product_cat');

    if (!empty($terms) && !\is_wp_error($terms)) {
        $filters['range'] = array_values(array_map(static fn($term) => $term->slug, $terms));
    }

    foreach (['pa_colour' => 'colour', 'pa_board-width' => 'width'] as $taxonomy => $key) {
        $values = \get_the_terms($product_id, $taxonomy);

        if (!empty($values) && !\is_wp_error($values)) {
            $filters[$key] = array_values(array_map(static fn($term) => $term->slug, $values));
        }
    }

    return $filters;
}
