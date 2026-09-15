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
 * @param \WP_Term $term The category.
 * @return array<int> Product IDs.
 */
function get_product_ids(\WP_Term $term): array
{
    $query = new \WP_Query([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 500,
        'fields' => 'ids',
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
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
