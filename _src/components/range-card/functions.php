<?php

namespace Granola\Components\RangeCard;

/**
 * Build the range cards for a shop category page.
 *
 * A category like Composite Decking holds 151 products across several named
 * collections. Listing all of them flat is what the live page does today, so
 * this presents the collections first and lets the grid below stay for people
 * who want the full list.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'items' => [],
        'heading' => '',
        'subheading' => '',
        'meta_prefix' => '',
        'intro' => '',
        'source' => 'automatic', // automatic, manual.
        'parent_category' => null,
        'ranges' => [],
        'swatch_limit' => 6,
        'object' => \Granola\WordPress\PageObject::get(),
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'range-card',
        'wp-block',
        'animate',
        'alignfull',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Work out which ranges to show.
    // -------------------------------------------------------------------------
    if ($args['source'] === 'manual') {
        $args['items'] = build_manual_items($args);
    } else {
        $args['items'] = build_automatic_items($args);
    }

    // -------------------------------------------------------------------------
    // Bail early if there is nothing to show. A category with no child
    // collections (accessories, for example) simply does not get this section.
    // -------------------------------------------------------------------------
    if (empty($args['items'])) {
        return null;
    }

    return $args;
}

/**
 * Which category are we showing the collections of.
 *
 * Normally the term being viewed, but a page can name one explicitly so the
 * section works outside a category archive too.
 *
 * @param array $args The component args.
 * @return \WP_Term|null The parent category.
 */
function resolve_parent_term(array $args): ?\WP_Term
{
    if (!empty($args['parent_category'])) {
        $term_id = is_object($args['parent_category'])
            ? $args['parent_category']->term_id
            : (int) $args['parent_category'];

        $term = \get_term($term_id, 'product_cat');

        return (!empty($term) && !\is_wp_error($term)) ? $term : null;
    }

    $object = $args['object'] ?? null;

    if ($object instanceof \WP_Term && $object->taxonomy === 'product_cat') {
        return $object;
    }

    return null;
}

/**
 * Derive the ranges from the child categories of the term being viewed.
 *
 * @param array $args The component args.
 * @return array The range items.
 */
function build_automatic_items(array $args): array
{
    $parent = resolve_parent_term($args);

    if (empty($parent)) {
        return [];
    }

    $children = \get_terms([
        'taxonomy' => 'product_cat',
        'parent' => $parent->term_id,
        'hide_empty' => true,
    ]);

    if (\is_wp_error($children) || empty($children)) {
        return [];
    }

    $items = [];

    foreach ($children as $term) {
        $items[] = build_item_from_term($term, $args['swatch_limit']);
    }

    return $items;
}

/**
 * Build the ranges from an explicit list chosen in the editor.
 *
 * @param array $args The component args.
 * @return array The range items.
 */
function build_manual_items(array $args): array
{
    if (empty($args['ranges'])) {
        return [];
    }

    $items = [];

    foreach ($args['ranges'] as $row) {
        $term = null;

        if (!empty($row['product_category'])) {
            $term_id = is_object($row['product_category'])
                ? $row['product_category']->term_id
                : (int) $row['product_category'];

            $term = \get_term($term_id, 'product_cat');
        }

        if (empty($term) || \is_wp_error($term)) {
            continue;
        }

        $item = build_item_from_term($term, $args['swatch_limit']);

        // Anything filled in by hand wins over what we worked out.
        foreach (['name', 'line', 'image_id', 'url'] as $key) {
            if (!empty($row[$key])) {
                $item[$key] = $row[$key];
            }
        }

        if (!empty($row['chips'])) {
            $item['chips'] = array_values(array_filter(array_map('trim', explode("\n", $row['chips']))));
        }

        $items[] = $item;
    }

    return $items;
}

/**
 * Turn one product category term into a range card.
 *
 * @param \WP_Term $term The product category.
 * @param int $swatch_limit How many colour swatches to show.
 * @return array The range item.
 */
function build_item_from_term(\WP_Term $term, int $swatch_limit): array
{
    $product_ids = get_product_ids_in_term($term);
    $swatches = build_swatches($product_ids, $swatch_limit);

    // No product category on this site carries a term image, so fall back to
    // the first product in the range rather than rendering a card with a hole
    // where the design puts its largest element.
    $image_id = (int) \get_term_meta($term->term_id, 'thumbnail_id', true);

    if (empty($image_id) && !empty($swatches)) {
        $image_id = $swatches[0]['image_id'];
    }

    return [
        'term' => $term,
        'name' => $term->name,
        'line' => $term->description,
        'url' => \get_term_link($term),
        'image_id' => $image_id,
        'chips' => [],
        'swatches' => $swatches,
        'colour_count' => count($product_ids),
        'from_price' => get_lowest_price_in_term($term),
    ];
}

/**
 * The published products inside a term, including any descendants of it.
 *
 * @param \WP_Term $term The product category.
 * @return array<int> Product IDs.
 */
function get_product_ids_in_term(\WP_Term $term): array
{
    $query = new \WP_Query([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 100,
        'fields' => 'ids',
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'tax_query' => [
            [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $term->term_id,
                'include_children' => true,
            ],
        ],
    ]);

    return $query->posts;
}

/**
 * Build the colour swatch row from the products in a range.
 *
 * @param array<int> $product_ids The products in the range.
 * @param int $limit How many to show.
 * @return array The swatches.
 */
function build_swatches(array $product_ids, int $limit): array
{
    $swatches = [];

    foreach ($product_ids as $product_id) {
        if (count($swatches) >= $limit) {
            break;
        }

        $image_id = (int) \get_post_thumbnail_id($product_id);

        if (empty($image_id)) {
            continue;
        }

        $swatches[] = [
            'image_id' => $image_id,
            'name' => \get_the_title($product_id),
        ];
    }

    return $swatches;
}

/**
 * The lowest price of an actual board in a range.
 *
 * Two traps here, both of which produce a confidently wrong number.
 *
 * A board is a variable product whose variations are the board itself plus its
 * two samples, carried on the pa_sample-size attribute as full, large and
 * small. The small sample is free and the large one is a few pounds, so the
 * naive minimum across a range prints "From £0 per board". Only the "full"
 * variation is the board.
 *
 * And wc_product_meta_lookup is not reliable here: it has no row at all for a
 * variable parent, and the rows it does have can lag a bulk price update (seen
 * locally holding 106.32 where _price was 110.88). So read _price directly.
 *
 * @param \WP_Term $term The product category.
 * @return string The formatted price, or an empty string if there is none.
 */
function get_lowest_price_in_term(\WP_Term $term): string
{
    global $wpdb;

    if (!function_exists('wc_price')) {
        return '';
    }

    $term_taxonomy_ids = get_term_taxonomy_ids($term);

    if (empty($term_taxonomy_ids)) {
        return '';
    }

    $placeholders = implode(',', array_fill(0, count($term_taxonomy_ids), '%d'));

    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
    // The board: the "full" variation of a variable product. Variations carry
    // no term of their own, so the category is matched on the parent.
    $board_price = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT MIN(CAST(price.meta_value AS DECIMAL(12,4)))
             FROM {$wpdb->postmeta} AS price
             INNER JOIN {$wpdb->posts} AS variation
                ON variation.ID = price.post_id
               AND variation.post_type = 'product_variation'
               AND variation.post_status = 'publish'
             INNER JOIN {$wpdb->postmeta} AS size
                ON size.post_id = variation.ID
               AND size.meta_key = 'attribute_pa_sample-size'
               AND size.meta_value = 'full'
             INNER JOIN {$wpdb->term_relationships} AS relationships
                ON relationships.object_id = variation.post_parent
             WHERE price.meta_key = '_price'
               AND relationships.term_taxonomy_id IN ({$placeholders})
               AND price.meta_value != ''
               AND CAST(price.meta_value AS DECIMAL(12,4)) > 0",
            $term_taxonomy_ids
        )
    );

    // Accessories, fixings and subframes are simple products and have no
    // variations, so they are priced on the product itself.
    $simple_price = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT MIN(CAST(price.meta_value AS DECIMAL(12,4)))
             FROM {$wpdb->postmeta} AS price
             INNER JOIN {$wpdb->posts} AS product
                ON product.ID = price.post_id
               AND product.post_type = 'product'
               AND product.post_status = 'publish'
             INNER JOIN {$wpdb->term_relationships} AS relationships
                ON relationships.object_id = product.ID
             WHERE price.meta_key = '_price'
               AND relationships.term_taxonomy_id IN ({$placeholders})
               AND price.meta_value != ''
               AND CAST(price.meta_value AS DECIMAL(12,4)) > 0",
            $term_taxonomy_ids
        )
    );
    // phpcs:enable

    // Prefer the board price. Falling back to the simple-product minimum keeps
    // accessory categories working without letting a sample undercut a board.
    $price = !empty($board_price) ? $board_price : $simple_price;

    if (empty($price)) {
        return '';
    }

    return \wc_price($price);
}

/**
 * The term_taxonomy_ids for a term and everything beneath it.
 *
 * @param \WP_Term $term The product category.
 * @return array<int> The term taxonomy IDs.
 */
function get_term_taxonomy_ids(\WP_Term $term): array
{
    $term_ids = array_merge([$term->term_id], \get_term_children($term->term_id, 'product_cat'));
    $term_taxonomy_ids = [];

    foreach ($term_ids as $term_id) {
        $child = \get_term($term_id, 'product_cat');

        if (!empty($child) && !\is_wp_error($child)) {
            $term_taxonomy_ids[] = (int) $child->term_taxonomy_id;
        }
    }

    return $term_taxonomy_ids;
}
