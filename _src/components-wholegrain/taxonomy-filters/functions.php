<?php

namespace Granola\Components\TaxonomyFilters;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'current_item' => 0,
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
    $all_link = false;

    // Initialize button classes
    $button_classes = [
        'g-button',
        'taxonomy-filters__item',
    ];
    $button_classes_all = $button_classes;

    $post_type = get_post_type();

    if (!empty($args['object'])) {
        $object = $args['object'];

        if ($object instanceof \WP_Term) {
            $args['taxonomy'] = $object->taxonomy;
            $args['current_item'] = $object->term_id;

            // The 'first' post type is selected if this taxonomy is registered to multiple post types.
            $post_type = get_post_type();
            $all_link = \get_post_type_archive_link($post_type);
        } elseif ($object instanceof \WP_Post_Type) {
            // Assume the taxonomy is 'category' by default.
            // Additional logic (or multiple filters) needed if multiple taxonomies are registered to this post type.
            $args['taxonomy'] = 'category';

            $button_classes_all[] = 'taxonomy-filters__item--current';
            $all_link = \get_post_type_archive_link($post_type);
        }
    }

    if (empty($args['taxonomy'])) {
        return $items;
    }

    // Check for active term from query parameter if preserve_url is enabled
    $current_term_slug = '';
    if ($args['preserve_url'] && isset($_GET[$args['taxonomy']])) {
        $current_term_slug = sanitize_text_field($_GET[$args['taxonomy']]);
    }

    // Prepare URLs based on preserve_url setting
    if ($args['preserve_url']) {
        // Get current URL without query parameters
        $current_url = strtok($_SERVER['REQUEST_URI'], '?');
        $all_link = $current_url;

        // Mark "All" as current if no filter is active
        if (empty($current_term_slug)) {
            $button_classes_all[] = 'taxonomy-filters__item--current';
        }
    }

    $all = [
        'title' => \_x('All', 'Category filter clear button text', 'granola'),
        'url' => $all_link,
        'classes' => $button_classes_all,
    ];

    // Get post IDs for the current post type to filter terms
    $post_ids = [];
    if (!empty($post_type)) {
        $posts = \get_posts([
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'publish',
        ]);
        $post_ids = $posts;
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

    foreach ($terms as $key => $item) {
        // Generate URL based on preserve_url setting
        if ($args['preserve_url']) {
            $current_url = strtok($_SERVER['REQUEST_URI'], '?');
            $item_url = add_query_arg($args['taxonomy'], $item->slug, $current_url);
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
        } elseif (empty($args['preserve_url']) && $args['current_item'] === $item->term_id) {
            $items[$key]['classes'][] = 'taxonomy-filters__item--current';
        }
    }

    array_unshift($items, $all);

    return $items;
}
