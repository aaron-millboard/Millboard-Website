<?php

namespace Granola\Components\TaxonomyFilters;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'current_item_id' => 0,
        'label' => \__('Filter by', 'granola'),
        'show_images' => false,
        'preserve_url' => false,
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (\is_search()) {
        return null;
    }

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'taxonomy-filters',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // Add taxonomy-specific class if taxonomy is set
    if (!empty($args['taxonomy'])) {
        $args['classes'][] = 'taxonomy-filters--' . $args['taxonomy'];
    }

    $args['items'] = get_taxonomy_items($args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['items'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

function get_taxonomy_items($args): array
{
    $items = [];

    if (!empty($args['object'])) {
        if ($args['object'] instanceof \WP_Term) {
            $args['taxonomy'] = $args['object']->taxonomy;
            $args['current_item_id'] = $args['object']->term_id;
        } elseif ($args['object'] instanceof \WP_Post_Type) {
            // Assume the taxonomy is 'category' by default.
            $args['taxonomy'] = 'category';
        }
    }

    if (empty($args['taxonomy'])) {
        return $items;
    }

    $post_type = get_post_type();

    // Get current URL without query parameters.
    $current_url = strtok($_SERVER['REQUEST_URI'], '?');

    // Check for active term from query parameter if preserve_url is enabled
    $current_term_slug = '';
    if ($args['preserve_url'] && isset($_GET[$args['taxonomy']])) {
        $current_term_slug = \sanitize_text_field($_GET[$args['taxonomy']]);
    }

    // Get post IDs for the current post type to filter terms
    $post_ids = [];
    if (!empty($post_type)) {
        $post_query = new \WP_Query([
            'post_type' => $post_type,
            'posts_per_page' => 1000, // arbitrary large number.
            'fields' => 'ids',
            'post_status' => 'publish',

            // Query optimisation.
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        $post_ids = $post_query->posts;
    }

    $term_args = [
        'taxonomy' => $args['taxonomy'],
        'parent' => 0,
        'hide_empty' => true,
    ];

    // Filter by post type if we have post IDs
    if (!empty($post_ids)) {
        $term_args['object_ids'] = $post_ids;
    }

    $terms = \get_terms($term_args);

    if (empty($terms)) {
        return $items;
    }

    // Initialize button classes.
    $button_classes = [
        'g-button',
        'taxonomy-filters__item',
    ];

    foreach ($terms as $key => $item) {
        // Generate URL based on preserve_url setting
        if (!empty($args['preserve_url'])) {
            $item_url = \add_query_arg([
                $args['taxonomy'] => $item->slug,
            ], $current_url);
        } else {
            $item_url = \get_term_link($item->slug, $item->taxonomy);
        }

        $items[$key] = [
            'title' => $item->name,
            'url' => $item_url,
            'classes' => $button_classes,
        ];

        // Add image if show_images is enabled and term has an image
        if ($args['show_images']) {
            $term_image = \get_field('image', $item);
            if ($term_image && !empty($term_image['sizes']['thumbnail'])) {
                $items[$key]['image'] = $term_image['sizes']['thumbnail'];
                $items[$key]['image_alt'] = $term_image['alt'] ?? $item->name;
            }
        }

        // Check if current based on preserve_url mode
        if (!empty($args['preserve_url']) && $current_term_slug === $item->slug) {
            $items[$key]['classes'][] = 'taxonomy-filters__item--current';
        } elseif (empty($args['preserve_url']) && $args['current_item_id'] === $item->term_id) {
            $items[$key]['classes'][] = 'taxonomy-filters__item--current';
        }
    }

    // Add reset link that shows "All" posts.
    $reset_item = get_reset_item($args);
    if (!empty($reset_item)) {
        array_unshift($items, $reset_item);
    }

    return $items;
}

function get_reset_item($args): array
{
    $post_type = get_post_type();

    if (!empty($args['object'])) {
        if ($args['object'] instanceof \WP_Term || $args['object'] instanceof \WP_Post_Type) {
            $url = \get_post_type_archive_link($post_type);

            if ($args['object'] instanceof \WP_Post_Type) {
                $classes[] = 'taxonomy-filters__item--current';
            }
        }
    }

    $classes = [
        'g-button',
        'taxonomy-filters__item',
    ];

    // Prepare URLs based on preserve_url setting
    if (!empty($args['preserve_url'])) {
        $url = strtok($_SERVER['REQUEST_URI'], '?');

        // Check for active term from query parameter if preserve_url is enabled
        $current_term_slug = '';
        if (isset($_GET[$args['taxonomy']])) {
            $current_term_slug = \sanitize_text_field($_GET[$args['taxonomy']]);
        }

        // Mark "All" as current if no filter is active
        if (empty($current_term_slug)) {
            $button_classes_all[] = 'taxonomy-filters__item--current';
        }
    }

    return [
        'title' => \_x('All', 'Category filter clear button text', 'granola'),
        'url' => $url,
        'classes' => $classes,
    ];
}
