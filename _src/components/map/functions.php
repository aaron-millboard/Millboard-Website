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

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}


function get_energy_type_label_by_slug(string $slug): string
{
    $labels_by_slug = [
        'biomass' => \__('Biomass', 'granola'),
        'solar' => \__('Solar', 'granola'),
        'storage' => \__('Energy storage', 'granola'),
        'waste_to_energy' => \__('Waste to energy', 'granola'),
        'wind' => \__('Wind', 'granola'),
    ];

    return $labels_by_slug[$slug];
}

function get_plant_operational_status_label_by_slug($slug): string
{
    $labels_by_slug = [
        'operational' => \__('Operational', 'granola'),
        'underauthorization' => \__('Awaiting authorisation', 'granola'),
        'underconstruction' => \__('Under construction', 'granola'),
    ];

    return array_key_exists($slug, $labels_by_slug) ? $labels_by_slug[$slug] : $labels_by_slug['operational'];
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

function get_country_label_by_two_letter_code($twoletter): string|array
{
    $labels_by_twoletter = [
        'de' => \__('Germany', 'granola'),
        'es' => \__('Spain', 'granola'),
        'fr' => \__('France', 'granola'),
        'fi' => \__('Finland', 'granola'),
        'it' => \__('Italy', 'granola'),
        'no' => \__('Norway', 'granola'),
        'se' => \__('Sweden', 'granola'),
        'uk' => \__('UK', 'granola'),
        'us' => \__('USA', 'granola'),
        'pt' => \__('Portugal', 'granola'),
        'de' => \__('Germany', 'granola'),
        'be' => \__('Belgium', 'granola'),
    ];

    if ($twoletter === 'all') {
        return $labels_by_twoletter;
    }

    return array_key_exists($twoletter, $labels_by_twoletter) ? $labels_by_twoletter[$twoletter] : 'NAH';
}

/**
 * Converts plants data to countries  data for the "countries" tab of the new map.
 *
 * @param [array] $plants_data - array of plant data.
 * @return [array] $countires_data - array of countries data.
 */
function get_countries_data($plants_data)
{
    if (empty($plants_data)) {
        return [];
    }

    // Setup countries array.
    $countries = [];
    $country_labels = get_country_label_by_two_letter_code('all');
    $countries_ordered_by_size = \Granola\Components\Map\Filters\order_countries_by_totals($country_labels);

    foreach ($countries_ordered_by_size as $key => $country_name) {
        $countries[$key] = [
            'no_wind' => 0,
            'no_solar' => 0,
            'installed_capacity' => 0,
            'co2_saved' => 0,
            'annual' => 0,
            'country' => '',
        ];
    }

    // Loop over plants data and calculate totals.
    foreach ($plants_data as $key => $plant) {
        $countries[$plant['country']] = [
            'no_wind' => ($plant['type'] === 'wind' ? $countries[$plant['country']]['no_wind'] + 1 : $countries[$plant['country']]['no_wind']),
            'no_solar' =>  ($plant['type'] === 'solar' ? $countries[$plant['country']]['no_solar'] + 1 : $countries[$plant['country']]['no_solar']),
            'installed_capacity' => $countries[$plant['country']]['installed_capacity'] + (int) filter_var($plant['capacity'], \FILTER_SANITIZE_NUMBER_INT),
            'co2_saved' =>  $countries[$plant['country']]['co2_saved'] + (int) filter_var($plant['co2_saved'], \FILTER_SANITIZE_NUMBER_INT),
            'annual' =>  $countries[$plant['country']]['annual'] + (int) filter_var($plant['annual'], \FILTER_SANITIZE_NUMBER_INT),
            'country' => $country_labels[$plant['country']],
        ];
    }

    return $countries;
}
