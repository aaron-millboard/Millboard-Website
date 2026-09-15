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
        'exclude_categories' => [],
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

    // Not every child category is a collection. Under Composite Decking the
    // board ranges sit alongside Decking Accessories, Edging, Fascias and
    // Subframes, and nothing in the data tells them apart: "has children" does
    // not work, because Enhanced Grain has 126mm and 176mm beneath it. So the
    // page says which to leave out.
    $excluded = array_map('intval', (array) $args['exclude_categories']);

    $items = [];

    foreach ($children as $term) {
        if (in_array((int) $term->term_id, $excluded, true)) {
            continue;
        }

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

    // No product category on this site carries a term image, so fall back to a
    // product photograph. Deliberately NOT the first swatch: a swatch is a flat
    // crop of the board surface, and using one here replaced the project shot
    // at the top of the card with a slab of texture.
    $image_id = (int) \get_term_meta($term->term_id, 'thumbnail_id', true);

    if (empty($image_id)) {
        $image_id = find_project_image($product_ids);
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
    $seen = [];

    foreach ($product_ids as $product_id) {
        if (count($swatches) >= $limit) {
            break;
        }

        $image_id = find_swatch_image($product_id);

        if (empty($image_id) || isset($seen[$image_id])) {
            continue;
        }

        $seen[$image_id] = true;

        $swatches[] = [
            'image_id' => $image_id,
            'name' => \get_the_title($product_id),
        ];
    }

    return $swatches;
}

/**
 * A project photograph for the top of the card.
 *
 * The first product in the range that has a featured image. These are lifestyle
 * and project shots, which is what the design puts here.
 *
 * @param array<int> $product_ids The products in the range.
 * @return int The attachment ID, or 0.
 */
function find_project_image(array $product_ids): int
{
    foreach ($product_ids as $product_id) {
        $image_id = (int) \get_post_thumbnail_id($product_id);

        if ($image_id > 0) {
            return $image_id;
        }
    }

    return 0;
}

/**
 * The colour swatch for a product.
 *
 * These squares are 26px. A lifestyle photograph shrunk to that reads as a
 * brown smudge, so a real swatch image is wanted: the media library holds them
 * titled "<SKU>_<Range>_<Colour>_Swatch".
 *
 * Coverage is partial. Matching by SKU finds 29 of the 44 board products,
 * and Weathered Oak and Lasta-Grip have no swatch at all, so this falls back
 * to the same colour in another board width before giving up and using the
 * product image. A card never loses its swatch row.
 *
 * @param int $product_id The product.
 * @return int The attachment ID, or 0.
 */
function find_swatch_image(int $product_id): int
{
    global $wpdb;

    $product = \function_exists('wc_get_product') ? \wc_get_product($product_id) : null;

    if (!empty($product)) {
        $sku = (string) $product->get_sku();

        if ($sku !== '') {
            $found = find_swatch_by_title([$sku . '%', '%Swatch%']);

            if ($found > 0) {
                return $found;
            }
        }

        // Same colour, same range, different board width: the 126mm boards
        // share a colour with the 176mm ones, which is where the swatches are.
        //
        // Held to the SKU family (the first three characters, MDE for Enhanced
        // Grain, MDL for Lasta-Grip, MDW for Weathered Oak) on purpose. Colour
        // alone crossed ranges and put an Enhanced Grain swatch on the
        // Lasta-Grip card, which has a different surface entirely. A product
        // photograph is a better answer than another range's texture.
        $colour = $product->get_attribute('pa_colour');

        if ($colour !== '' && strlen($sku) >= 3) {
            $found = find_swatch_by_title([substr($sku, 0, 3) . '%', '%' . $colour . '%', '%Swatch%']);

            if ($found > 0) {
                return $found;
            }
        }

        // The plain colour set, titled "<Colour> swatch" with no range prefix.
        // Square 2048s, so they crop cleanly to the little squares. This is
        // what covers Lasta-Grip and Weathered Oak, which have no swatch of
        // their own, rather than leaving a project photo at 26px.
        if ($colour !== '') {
            $found = find_swatch_by_title([$colour . ' swatch']);

            if ($found > 0) {
                return $found;
            }
        }
    }

    return (int) \get_post_thumbnail_id($product_id);
}

/**
 * The first attachment whose title matches every pattern.
 *
 * "Swatch Length" is a long thin strip rather than a square, so it sorts last.
 *
 * @param array<string> $patterns LIKE patterns, already wildcarded.
 * @return int The attachment ID, or 0.
 */
function find_swatch_by_title(array $patterns): int
{
    global $wpdb;

    $where = [];
    $values = [];

    foreach ($patterns as $pattern) {
        $where[] = 'post_title LIKE %s';
        $values[] = $pattern;
    }

    $values[] = '%Swatch Length%';

    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
               AND " . implode(' AND ', $where) . "
             ORDER BY (post_title LIKE %s) ASC, ID ASC
             LIMIT 1",
            $values
        )
    );
    // phpcs:enable

    return (int) $id;
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
