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

    if ($args['content_type'] !== 'custom' && empty($args['items'])) {
        $args['items'] = get_item_data($args);

        // Determine post types for label
        $post_types_for_label = $args['content_type'];
        if (!empty($args['sources']) && is_array($args['sources'])) {
            $post_types_for_label = $args['sources'];
        }

        if (!empty($post_types_for_label)) {
            // Handle multiple post types
            if (is_array($post_types_for_label)) {
                $labels = [];
                foreach ($post_types_for_label as $post_type) {
                    $post_type_object = \get_post_type_object($post_type);
                    if (!empty($post_type_object)) {
                        $labels[] = $post_type_object->label;
                    }
                }
                if (!empty($labels)) {
                    $args['search_geolocate_text'] = sprintf(
                        // translators: Content type(s), e.g. "installers" or "installers and distributors".
                        \__('Find %s near me', 'granola'),
                        implode(\__(' and ', 'granola'), $labels)
                    );
                }
            } else {
                $post_type_object = \get_post_type_object($post_types_for_label);
                if (!empty($post_type_object)) {
                    $args['search_geolocate_text'] = sprintf(
                        // translators: Content type plural, e.g. "installers".
                        \__('Find %s near me', 'granola'),
                        $post_type_object->label
                    );
                }
            }
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

    // Generate filters if multiple post types are present
    $args['filters'] = generate_filters($args);

    $args['search_submit'] = [
        'type' => 'submit',
        'content' => \__('Search', 'granola'),
        'classes' => [
            'g-button',
            'g-button--icon',
            'map__search__submit',
        ],
    ];

    // Selectable distance dropdown options.
    $args['distances'] = [
        10,
        15,
        25,
        50,
        100,
        150,
        250,
        500,
    ];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

function get_item_data($args): array|null
{
    $items = [];

    // Determine which post types to query
    $post_types = $args['content_type'];
    if (!empty($args['sources']) && is_array($args['sources'])) {
        $post_types = $args['sources'];
    }

    $post_query = new \WP_Query([
        'post_type' => $post_types,
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
        $address = \get_field('address', $wp_post_id);
        $lat = \get_field('address_lat', $wp_post_id);
        $lng = \get_field('address_lng', $wp_post_id);

        $items[] = [
            'id' => $wp_post_id,
            'title' => $wp_post->post_title,
            'address' => $address,
            'phone' => \get_field('phone', $wp_post_id),
            'email' => \get_field('email', $wp_post_id),
            'website' => \get_field('website', $wp_post_id),
            'url' => \get_permalink($wp_post),
            'post' => $wp_post,
            'post_type' => $wp_post->post_type,
            'attributes' => [
                'class' => 'map__listing',
                'data-map-item-lat' => $lat,
                'data-map-item-lng' => $lng,
                'data-map-item-post-type' => $wp_post->post_type,
            ],
        ];
    }

    return $items;
}

function generate_filters($args): array
{
    $filters = [];

    // Only generate filters if there are multiple post types
    $post_types = $args['content_type'];
    if (!empty($args['sources']) && is_array($args['sources'])) {
        $post_types = $args['sources'];
    }

    // Check if we have multiple post types
    if (!is_array($post_types) || count($post_types) <= 1) {
        return $filters;
    }

    // Count items by post type
    $counts = [];
    if (!empty($args['items'])) {
        foreach ($args['items'] as $item) {
            $post_type = $item['post']->post_type;
            if (!isset($counts[$post_type])) {
                $counts[$post_type] = 0;
            }
            $counts[$post_type]++;
        }
    }

    // Create "All" filter button
    $filters[] = [
        'label' => \_x('All', 'Map filter for all post types', 'granola'),
        'value' => '',
        'count' => count($args['items']),
        'active' => true,
    ];

    // Create filter button for each post type
    foreach ($post_types as $post_type) {
        $post_type_object = \get_post_type_object($post_type);
        if (!empty($post_type_object)) {
            $count = isset($counts[$post_type]) ? $counts[$post_type] : 0;
            $filters[] = [
                'label' => $post_type_object->label,
                'value' => $post_type,
                'count' => $count,
                'active' => false,
            ];
        }
    }

    return $filters;
}

/**
 * Adds the Google API Key to an AJAX object's properties in granola-scripts via localization.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_localize_script/
 *
 * @param array $localizations An array of 'localizations' for granola-scripts.
 * @return array The filtered array of localizations for granola-scripts, with AJAX values conditionally added.
 */
function add_google_api_key_localization($localizations): array
{
    $api_key = \get_field('google_api_key', 'option');

    // Add Google API Key, if set.
    if (!empty($api_key)) {
        $localizations['google_api_key'] = $api_key;
    }

    return $localizations;
}
