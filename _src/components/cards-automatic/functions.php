<?php

namespace Granola\Components\CardsAutomatic;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'items' => [],
        'inner_attributes' => [
            'class' => ['cards__inner'],
        ],
        // Config.
        'card_source' => 'recent', // recent, selected.
        'limit' => 3,
        'columns' => null,
        'post_type' => 'post',
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'cards-automatic',
        'cards',
        'wp-block',
        'animate',
    ], $args['classes']);

    // ---------------------------------------
    // Set up the items.
    // ---------------------------------------
    if ($args['card_source'] === 'recent') {
        // ---------------------------------------
        // Make a query.
        // ---------------------------------------
        $query = [
            'post_type' => $args['post_type'],
            'posts_per_page' => $args['limit'],
            'exclude' => \get_the_ID(),
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
        ];

        $query = \apply_filters('granola/components/cards/query', $query);

        $query = new \WP_Query($query);
        $objects = $query->posts;
    } elseif ($args['card_source'] === 'selected') {
        if (!isset($args['selected']) || empty($args['selected'])) {
            return null;
        }
        $objects = $args['selected'];
    }

    $args['items'] = $objects;

    // ---------------------------------------
    // Bail early if no items.
    // ---------------------------------------
    if (empty($args['items'])) {
        return null;
    }

    // ---------------------------------------
    // Format the items.
    // ---------------------------------------
    foreach ($args['items'] as $key => $object) {
        if ($object instanceof \WP_Post) {
            $args['items'][$key] = [
                'object' => $object,
                // 'orientation' => isset($args['orientation']) ? $args['orientation'] : null,
            ];
        } else {
            $args['items'][$key] = $object;
        }
    }

    $args = \Granola\Components\Cards\handle_shared_card_args_logic($args);

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Hooks into the theme.json data and allows an override to be passed in.
 *
 * @param WP_Theme_JSON_Data $theme_json The theme.json object.
 * @return WP_Theme_JSON_Data With updated data.
 */
function set_cards_background_color_palette(\WP_Theme_JSON_Data $theme_json): \WP_Theme_JSON_Data
{
    $new_palette = [
        [
            'name' => 'Contrast',
            'slug' => 'contrast',
            'color' => '#008080',
        ],
    ];

    return \Granola\Helpers::override_theme_json_with_new_palette_for_block('acf/cards-automatic', $new_palette, $theme_json);
}
