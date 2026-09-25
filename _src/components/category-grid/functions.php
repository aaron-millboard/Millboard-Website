<?php

namespace Granola\Components\CategoryGrid;

/**
 * The product grid on a shop category page.
 *
 * Every product is rendered into the HTML. Filtering, sorting and "load more"
 * all work on what is already there, for two reasons: the page has to be
 * indexable, so the products cannot arrive by fetch; and filtering in place
 * changes no URL, so it cannot spawn the facet URLs that already strain index
 * coverage on this site.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'per_page' => 12,
        'show_sort' => true,
        'show_filters' => true,
        'sample_url' => '',
        'empty_heading' => '',
        'empty_text' => '',
        'product_category' => null,
        'object' => \Granola\WordPress\PageObject::get(),
        'uid' => \wp_unique_id('category-grid-'),
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'category-grid',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    $term = resolve_term($args);

    if (empty($term)) {
        return null;
    }

    $args['term'] = $term;
    $args['product_ids'] = get_product_ids($term);

    // -------------------------------------------------------------------------
    // Bail early if the category holds nothing.
    // -------------------------------------------------------------------------
    if (empty($args['product_ids'])) {
        return null;
    }

    if (empty($args['heading'])) {
        $args['heading'] = $term->name;
    }

    $args['filter_groups'] = $args['show_filters'] ? build_filter_groups($args['product_ids'], $term) : [];
    $args['per_page'] = max(1, (int) $args['per_page']);

    return $args;
}

/**
 * Which category the grid is showing.
 *
 * @param array $args The component args.
 * @return \WP_Term|null The category.
 */
function resolve_term(array $args): ?\WP_Term
{
    if (!empty($args['product_category'])) {
        $term_id = is_object($args['product_category'])
            ? $args['product_category']->term_id
            : (int) $args['product_category'];

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
 * The products in the category, including its sub categories.
 *
 * Minus the branches marked as not being the category's own products. A
 * category holds more than the thing it is named after: Composite Decking
 * owns Edging, Fascias, Subframes and Decking Accessories as well as the five
 * board ranges, so listing it whole put bullnose boards, joists and screws in
 * a grid headed "All composite decking".
 *
 * Those branches are not moved out of the tree, because they belong there and
 * because 85 products carry Composite Decking as their Yoast primary category,
 * which is what builds their URL. They are flagged instead.
 *
 * @param \WP_Term $term The category.
 * @return array<int> Product IDs.
 */
function get_product_ids(\WP_Term $term): array
{
    $excluded = get_excluded_term_ids($term);

    $query = static function (array $tax_query): array {
        $q = new \WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 500,
            'fields' => 'ids',
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
            'tax_query' => $tax_query,
        ]);

        return $q->posts;
    };

    $in_category = [
        'taxonomy' => 'product_cat',
        'field' => 'term_id',
        'terms' => $term->term_id,
        'include_children' => true,
    ];

    if (empty($excluded)) {
        return $query([$in_category]);
    }

    $ids = $query([
        $in_category,
        [
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $excluded,
            'operator' => 'NOT IN',
            'include_children' => true,
        ],
    ]);

    // A product can sit in an excluded branch AND in a range that is kept, and
    // dropping it then takes a product off the page that plainly belongs on it.
    // On de-de ten of the twelve Envello Décor profiles are tagged into
    // Cladding Accessories as well as Décor, which took the whole Décor range
    // off the cladding page: 23 products where the same page shows 33
    // everywhere else.
    //
    // Retagging them is not the fix. Yoast's primary category builds the
    // product URL, and it is Cladding Accessories on nine of the ten, so
    // removing the tag moves nine live German product URLs.
    //
    // So the exclusion is narrowed instead: a product is dropped only when it
    // belongs to NO kept branch of this category. Measured across all six
    // locales, this changes nothing anywhere except those ten on de-de.
    $kept = get_kept_child_ids($term, $excluded);

    if (!empty($kept)) {
        $rescued = $query([
            'relation' => 'AND',
            [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $excluded,
                'include_children' => true,
            ],
            [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $kept,
                'include_children' => true,
            ],
        ]);

        if (!empty($rescued)) {
            $ids = array_values(array_unique(array_merge($ids, $rescued)));

            // The merge broke the title order the two queries each had.
            $titles = [];

            foreach ($ids as $id) {
                $titles[$id] = \get_the_title($id);
            }

            uasort($titles, static fn($a, $b) => strcasecmp((string) $a, (string) $b));

            $ids = array_keys($titles);
        }
    }

    return $ids;
}

/**
 * The children of this category that are NOT excluded, and are not sitting
 * under something excluded.
 *
 * @param \WP_Term $term The category being shown.
 * @param array<int> $excluded The excluded child term IDs.
 * @return array<int> Term IDs that count as this category's own ranges.
 */
function get_kept_child_ids(\WP_Term $term, array $excluded): array
{
    $children = \get_terms([
        'taxonomy' => 'product_cat',
        'child_of' => $term->term_id,
        'hide_empty' => false,
        'fields' => 'ids',
    ]);

    if (\is_wp_error($children) || empty($children)) {
        return [];
    }

    $under_excluded = [];

    foreach ($excluded as $excluded_id) {
        $descendants = \get_terms([
            'taxonomy' => 'product_cat',
            'child_of' => (int) $excluded_id,
            'hide_empty' => false,
            'fields' => 'ids',
        ]);

        if (!\is_wp_error($descendants) && !empty($descendants)) {
            $under_excluded = array_merge($under_excluded, array_map('intval', $descendants));
        }
    }

    return array_values(array_diff(
        array_map('intval', $children),
        array_map('intval', $excluded),
        $under_excluded
    ));
}

/**
 * Sub categories flagged as not part of this category's own product list.
 *
 * Carried on the term rather than on the block so one setting serves every
 * locale and every page that lists the category. Set `shop_grid_exclude` to 1
 * on a term to drop it and everything under it.
 *
 * @param \WP_Term $term The category being shown.
 * @return array<int> Term IDs to exclude.
 */
function get_excluded_term_ids(\WP_Term $term): array
{
    $children = \get_terms([
        'taxonomy' => 'product_cat',
        'child_of' => $term->term_id,
        'hide_empty' => false,
        'fields' => 'ids',
    ]);

    if (\is_wp_error($children) || empty($children)) {
        return [];
    }

    $excluded = [];

    foreach ($children as $child_id) {
        if (\get_term_meta((int) $child_id, 'shop_grid_exclude', true)) {
            $excluded[] = (int) $child_id;
        }
    }

    return $excluded;
}

/**
 * The filter chips, built from the products actually on the page.
 *
 * Deriving them from the rendered products rather than from the whole taxonomy
 * means a chip can never return nothing.
 *
 * @param array<int> $product_ids The products.
 * @param \WP_Term $term The category being shown.
 * @return array The filter groups.
 */
function build_filter_groups(array $product_ids, \WP_Term $term): array
{
    $sources = [
        'range' => ['taxonomy' => 'product_cat', 'label' => \__('Range', 'granola')],
        'colour' => ['taxonomy' => 'pa_colour', 'label' => \__('Colour', 'granola')],
        'width' => ['taxonomy' => 'pa_board-width', 'label' => \__('Width', 'granola')],
    ];

    $groups = [];

    foreach ($sources as $key => $source) {
        $found = [];

        foreach ($product_ids as $product_id) {
            $terms = \get_the_terms($product_id, $source['taxonomy']);

            if (empty($terms) || \is_wp_error($terms)) {
                continue;
            }

            foreach ($terms as $found_term) {
                // The range chips are the collections, so only the level
                // directly beneath the category being viewed qualifies. Without
                // this the chips include the widths and the category itself.
                if ($key === 'range' && (int) $found_term->parent !== (int) $term->term_id) {
                    continue;
                }

                if (!isset($found[$found_term->slug])) {
                    $found[$found_term->slug] = [
                        'slug' => $found_term->slug,
                        'label' => $found_term->name,
                        'count' => 0,
                    ];
                }

                $found[$found_term->slug]['count']++;
            }
        }

        if (count($found) < 2) {
            // A group with one value filters nothing.
            continue;
        }

        ksort($found);

        $groups[] = [
            'key' => $key,
            'label' => $source['label'],
            'options' => array_values($found),
        ];
    }

    return $groups;
}
