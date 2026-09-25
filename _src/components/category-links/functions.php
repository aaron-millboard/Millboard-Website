<?php

namespace Granola\Components\CategoryLinks;

/**
 * A row of labelled links.
 *
 * Serves two sections of the category design that are the same pattern with a
 * different finish: the tools strip ("Plan it before you buy it"), which is
 * solid, and the related categories row ("Complete your project"), which is
 * outlined and carries a product count rather than a description.
 *
 * Not a change to navigation-tiles. That renders 207 published pages on en-gb
 * and puts a separate call to action inside each tile, where the design makes
 * the whole tile the link.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'meta_prefix' => '',
        'heading' => '',
        'style' => 'outline', // outline, solid.
        'items' => [],
    ], $args);

    $style = $args['style'] === 'solid' ? 'solid' : 'outline';

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'category-links',
        'category-links--' . $style,
        'wp-block',
        'alignfull',
    ], $args['classes']);

    $args['items'] = normalise_items($args['items']);

    // -------------------------------------------------------------------------
    // Bail early if there is nothing to link to.
    // -------------------------------------------------------------------------
    if (empty($args['items'])) {
        return null;
    }

    return $args;
}

/**
 * Resolve each row to a label, a destination and a second line.
 *
 * A row can name a product category instead of a link, in which case the
 * label, the URL and the count all come from the category. That keeps the
 * related categories row honest: the count is whatever is actually in there.
 *
 * @param array $items The rows.
 * @return array The items.
 */
function normalise_items(array $items): array
{
    $normalised = [];

    foreach ($items as $item) {
        $term = null;

        if (!empty($item['product_category'])) {
            $term_id = is_object($item['product_category'])
                ? $item['product_category']->term_id
                : (int) $item['product_category'];

            $found = \get_term($term_id, 'product_cat');

            if (!empty($found) && !\is_wp_error($found)) {
                $term = $found;
            }
        }

        $link = $item['link'] ?? [];
        $label = trim((string) ($item['label'] ?? ''));
        $detail = trim((string) ($item['detail'] ?? ''));
        $url = trim((string) ($link['url'] ?? ''));

        if (!empty($term)) {
            $label = $label !== '' ? $label : $term->name;
            $url = $url !== '' ? $url : (string) \get_term_link($term);

            if ($detail === '') {
                $count = count_products_in_term($term);

                if ($count > 0) {
                    $detail = sprintf(
                        // translators: %s: number of products in a category.
                        \_n('%s product', '%s products', $count, 'granola'),
                        \number_format_i18n($count)
                    );
                }
            }
        } elseif ($label === '') {
            $label = trim((string) ($link['title'] ?? ''));
        }

        if ($label === '' || $url === '') {
            continue;
        }

        $normalised[] = [
            'label' => $label,
            'detail' => $detail,
            'url' => $url,
            'target' => $link['target'] ?? '',
        ];
    }

    return $normalised;
}

/**
 * How many published products sit in a category, including its children.
 *
 * The term's own count is not used: it excludes descendants, so a parent like
 * Decking Accessories would under-report.
 *
 * @param \WP_Term $term The category.
 * @return int The count.
 */
function count_products_in_term(\WP_Term $term): int
{
    $query = new \WP_Query([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
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

    return (int) $query->found_posts;
}
