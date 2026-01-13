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

    $all_link = false;

    // Initialize button classes
    $button_classes = [
        'g-button',
        'taxonomy-filters__item',
    ];
    $button_classes_all = $button_classes;

    if (!empty($args['object'])) {
        $object = $args['object'];

        if ($object instanceof \WP_Term) {
            $args['taxonomy'] = $object->taxonomy;
            $args['current_item'] = $object->term_id;

            // The 'first' post type is selected if this taxonomy is registered to multiple post types.
            $taxonomy = \get_taxonomy($object->taxonomy);
            $all_link = \get_post_type_archive_link(reset($taxonomy->object_type));
        } elseif ($object instanceof \WP_Post_Type) {
            // Assume the taxonomy is 'category' by default.
            // Additional logic (or multiple filters) needed if multiple taxonomies are registered to this post type.
            $args['taxonomy'] = 'category';

            $button_classes_all[] = 'taxonomy-filters__item--current';
            $all_link = \get_post_type_archive_link($object->name);
        }
    }

    if (!empty($args['taxonomy'])) {
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

        $items = \get_terms($args['taxonomy']);

        if (!empty($items)) {
            foreach ($items as $key => $item) {
                // Generate URL based on preserve_url setting
                if ($args['preserve_url']) {
                    $current_url = strtok($_SERVER['REQUEST_URI'], '?');
                    $item_url = add_query_arg($args['taxonomy'], $item->slug, $current_url);
                } else {
                    $item_url = \get_term_link($item->slug, $item->taxonomy);
                }

                $args['items'][$key] = [
                    'title' => $item->name,
                    'url' => $item_url,
                    'classes' => $button_classes,
                ];

                // Add image if show_images is enabled and term has an image
                if ($args['show_images']) {
                    $term_image = \get_field('image', $item);
                    if ($term_image && !empty($term_image['sizes']['thumbnail'])) {
                        $args['items'][$key]['image'] = $term_image['sizes']['thumbnail'];
                        $args['items'][$key]['image_alt'] = $term_image['alt'] ?? $item->name;
                    }
                }

                // Check if current based on preserve_url mode
                if ($args['preserve_url']) {
                    if ($current_term_slug === $item->slug) {
                        $args['items'][$key]['classes'][] = 'taxonomy-filters__item--current';
                    }
                } else {
                    if ($args['current_item'] === $item->term_id) {
                        $args['items'][$key]['classes'][] = 'taxonomy-filters__item--current';
                    }
                }
            }

            array_unshift($args['items'], $all);
        }
    }

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
