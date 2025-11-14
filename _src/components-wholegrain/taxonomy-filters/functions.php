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
        'show' => true,
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if ($args['show'] === false) {
        return null;
    }

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'taxonomy-filters',
        'wp-block',
    ], $args['classes']);

    if (!empty($args['object'])) {
        $object = $args['object'];

        $button_classes = [
            'g-button',
            'g-button--small',
            'taxonomy-filters__item',
        ];
        $button_classes_all = $button_classes;

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
        $tax = \get_taxonomy($args['taxonomy']);

        $args['label'] = sprintf(
            // translators: taxonomy name.
            \__('Filter by %s', 'granola'),
            strtolower($tax->labels->singular_name)
        );

        $all = [
            'title' => \_x('All', 'Category filter clear button text', 'granola'),
            'url' => $all_link,
            'classes' => $button_classes_all,
        ];

        $items = \get_terms($args['taxonomy']);

        if (!empty($items)) {
            foreach ($items as $key => $item) {
                $args['items'][$key] = [
                    'title' => $item->name,
                    'url' => \get_term_link($item->slug, $item->taxonomy),
                    'classes' => $button_classes,
                ];

                if ($args['current_item'] === $item->term_id) {
                    $args['items'][$key]['classes'][] = 'taxonomy-filters__item--current';
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
