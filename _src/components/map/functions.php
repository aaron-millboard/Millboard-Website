<?php

namespace Granola\Components\Map;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'buttons' => [],
        'table_rows' => [],
        'content_type' => 'installer',
        'items' => [],
        'sidebar_heading' => [],
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'map',
        'wp-block',
    ], $args['classes']);

    $args['items'] = get_item_data($args);

    if (!empty($args['content_type'])) {
        $post_type_object = \get_post_type_object($args['content_type']);

        if (!empty($post_type_object)) {
            $args['search_description'] = sprintf(
                // translators: Content type plural, e.g. "installers".
                \__('Find %s near me', 'granola'),
                $post_type_object->label
            );
        }
    }

    $args['sidebar_heading'] = [
        'el' => 'h3',
        'content' => sprintf(
            \_n(
                // translators: the number of map results.
                'Displaying: %s result',
                'Displaying: %s results',
                count($args['items']),
                'granola'
            ),
            count($args['items'])
        ),
        'classes' => [
            'map__sidebar__heading',
        ],
    ];

    $args['search_submit'] = [
        'type' => 'submit',
        'content' => \__('Search', 'granola'),
        'classes' => [
            'g-button',
            'g-button--icon',
            'map__search__submit',
        ],
    ];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

function get_item_data($args): array|null
{
    $items = [];
    $post_query = new \WP_Query([
        'post_type' => $args['content_type'],
        'posts_per_page' => 500, //arbitrary large number.
        'status' => 'publish',
        'perm' => 'readable',

        // Query optimisation.
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);

    foreach ($post_query->posts as $wp_post) {
        $wp_post_id = $wp_post->ID;
        $items[] = [
            'id' => $wp_post_id,
            'title' => $wp_post->post_title,
            'address' => \get_field('address', $wp_post_id),
            'phone' => \get_field('phone', $wp_post_id),
            'email' => \get_field('email', $wp_post_id),
            'website' => \get_field('website', $wp_post_id),
            'url' => \get_permalink($wp_post),
            'post' => $wp_post,
        ];
    }

    return $items;
}
