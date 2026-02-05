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

    // // -------------------------------------------------------------------------
    // // Labels: Energy Types.
    // // -------------------------------------------------------------------------
    // $args['energytype'] = [
    //     'biomass' => \__('Biomass', 'granola'),
    //     'storage' => \__('Energy storage', 'granola'),
    //     'solar' => \__('Solar', 'granola'),
    //     'waste-to-energy' => \__('Waste to energy', 'granola'),
    //     'wind' => \__('Wind', 'granola')
    // ];

    // // -------------------------------------------------------------------------
    // // Labels: Country
    // // -------------------------------------------------------------------------
    // $args['country'] = [
    //     'de' => \__('Germany', 'granola'),
    //     'es' => \__('Spain', 'granola'),
    //     'fr' => \__('France', 'granola'),
    //     'fi' => \__('Finland', 'granola'),
    //     'it' => \__('Italy', 'granola'),
    //     'no' => \__('Norway', 'granola'),
    //     'se' => \__('Sweden', 'granola'),
    //     'uk' => \__('United Kingdom', 'granola'),
    //     'us' => \__('USA', 'granola'),
    // ];

    // $plants_data = get_plants_data();

    // if (empty($plants_data)) {
    //     return null;
    // }

    // // -------------------------------------------------------------------------
    // // Country Data.
    // // -------------------------------------------------------------------------
    // $country_content_data = $plants_data['data']['countries']['data'];
    // if (isset($plants_data['data']['countries']['stats'])) {
    //     // Get all countries from filter list.
    //     $countries = get_country_label_by_two_letter_code('all');
    //     // Order countries by size.
    //     $countries_ordered_by_size = \Granola\Components\Map\Filters\order_countries_by_totals($countries);

    //     // Loop over each country to get the label/name.
    //     foreach ($plants_data['data']['countries']['stats'] as $key => $country_stats) {
    //         // Check if it is one of our allowed countries.
    //         if (isset($countries_ordered_by_size[$key])) {
    //             // Get featured image from '$plants_data['data']['countries']['data']'.
    //             // Array is not keyed with 2-letter country codes to array filter to find.

    //             $needle = strtoupper($key);
    //             $matching_country = array_filter($country_content_data, function ($v) use ($needle) {
    //                 return $v['country_code'] === $needle; // when TRUE store value into result array
    //             });

    //             $countries_ordered_by_size[$key] = $country_stats;
    //             $countries_ordered_by_size[$key]['label'] = get_country_label_by_two_letter_code($key);

    //             if ($matching_country) {
    //                 $country = reset($matching_country);
    //                 $countries_ordered_by_size[$key]['featured_image'] = $country['featured_image'];
    //             }
    //         }
    //     }
    //     $args['countries_data'] = $countries_ordered_by_size;
    // } else {
    //     // Collect from looping over plant data.
    //     $args['countries_data'] = get_countries_data($plants_data['data']['plants']);
    // }

    // // -------------------------------------------------------------------------
    // // Loop over all the plants.
    // // Creates each of our table rows.
    // // -------------------------------------------------------------------------
    // foreach ($plants_data['data']['plants'] as $raw_row) {
    //     // Set default status if none is given.
    //     if (empty($raw_row['status']) || $raw_row['status'] === 'inoperation') {
    //         $opstatus = 'operational';
    //     } else {
    //         $opstatus = $raw_row['status'];
    //     }

    //     // Handle the language of the description.
    //     if (!empty($raw_row['descriptions'])) {
    //         if (defined('ICL_LANGUAGE_CODE')) {
    //             // phpcs:ignore
    //             switch (\ICL_LANGUAGE_CODE) {
    //                 case "en":
    //                     $description = $raw_row['descriptions']['en_description'];
    //                     break;
    //                 case "it":
    //                     $description = $raw_row['descriptions']['it_description'] ? $raw_row['descriptions']['it_description'] : $raw_row['descriptions']['en_description'];
    //                     break;
    //                 case "fr":
    //                     $description = $raw_row['descriptions']['fr_description'] ? $raw_row['descriptions']['fr_description'] : $raw_row['descriptions']['en_description'];
    //                     break;
    //                 case "es":
    //                     $description = $raw_row['descriptions']['es_description'] ? $raw_row['descriptions']['es_description'] : $raw_row['descriptions']['en_description'];
    //                     break;
    //                 default:
    //                     $description = $raw_row['descriptions']['en_description'];
    //                     break;
    //             }
    //         } else {
    //             $description = $raw_row['descriptions']['en_description'];
    //         }
    //     } else {
    //         $description = null;
    //     }

    //     // Processed item.
    //     $plants_data_row = [
    //         'name' => [
    //             'value' => $raw_row['name'],
    //             'value_display' => $raw_row['name'],
    //         ],
    //         'description' => [
    //             'value' => $description,
    //         ],
    //         'country' => [
    //             'value' => $raw_row['country'],
    //             'value_display' => get_country_label_by_two_letter_code($raw_row['country']),
    //         ],
    //         'energytype' => [
    //             'value' => json_encode($raw_row['technologies']),
    //             'value_display' => implode(', ', $raw_row['technologies'])
    //         ],
    //         'opstatus' => [
    //             'value' => $opstatus,
    //             'value_display' => get_plant_operational_status_label_by_slug($opstatus),
    //         ],
    //         'lat' => [
    //             'value' => $raw_row['latitude'],
    //         ],
    //         'lng' => [
    //             'value' => $raw_row['longitude'],
    //         ],
    //         'capacity_mw' => [
    //             'value' => $raw_row['capacity'] === 'N/A' ? 0 : $raw_row['capacity'],
    //             'value_display' => $raw_row['capacity'],
    //         ],
    //         'production_mwhyear' => [
    //             'value' => $raw_row['annual'] === 'N/A' ? 0 : $raw_row['annual'],
    //             'value_display' => $raw_row['annual'],
    //         ],
    //         'storage' => [
    //             'value' => $raw_row['storage'] === 'N/A' ? 0 : $raw_row['storage'],
    //             'value_display' => $raw_row['storage'],
    //         ],
    //         'households' => [
    //             'value' => $raw_row['households'],
    //         ],
    //         'co2_saved_tyear' => [
    //             'value' => $raw_row['co2_saved'] === 'N/A' ? 0 : $raw_row['co2_saved'],
    //             'value_display' => $raw_row['co2_saved'],
    //         ],
    //         // 'link_website_url' => !empty($raw_row['links'][0]) ? [
    //         //     'value' => $raw_row['links'][0]['url'],
    //         // ] : null,
    //         'image_url' => !empty($raw_row['featured_image']) ? [
    //             'value' => $raw_row['featured_image'],
    //         ] : null,
    //         'number_plant_units' => [
    //             'value' => $raw_row['units'] === 'N/A' ? 0 : $raw_row['units'],
    //             'value_display' => $raw_row['units'],
    //         ],
    //     ];

    //     if (!empty($raw_row['links'])) {
    //         $plants_data_row['links'] = $raw_row['links'];
    //     }

    //     $args['table_rows'][] = $plants_data_row;
    // }

    // // -------------------------------------------------------------------------
    // // Sort our table rows so that they are grouped by country.
    // // And by country order.
    // // -------------------------------------------------------------------------
    // $country_labels = get_country_label_by_two_letter_code('all');
    // $countries_ordered_by_size = \Granola\Components\Map\Filters\order_countries_by_totals($country_labels);

    // // Clear up countried order by size array.
    // foreach ($countries_ordered_by_size as $key => $country) {
    //     $countries_ordered_by_size[$key] = [];
    // }

    // // Put each row into array below the country the row sits in.
    // foreach ($args['table_rows'] as $key => $row) {
    //     // Insert row into countries_array using "country-code" as key.
    //     $countries_ordered_by_size[$row['country']['value']][] = $row;
    //     // Remove this table row data.
    //     unset($args['table_rows'][$key]);
    // }


    // // Put the rows back into the table in the correct order.
    // // Not the plants are given to us in alphabetical so no need to sort alphabetically inside a country.
    // foreach ($countries_ordered_by_size as $country_code => $country) {
    //     foreach ($country as $i => $row) {
    //         $args['table_rows'][] = $row;
    //     }
    // }

    // -------------------------------------------------------------------------
    // Buttons
    // -------------------------------------------------------------------------
    // Filter.
    $args['buttons']['filter'] = [
        'attributes' => [
            'aria-controls' => 'map-filter',
            'aria-expanded' => 'true',
            'class' => ['g-button', 'map__filters__toggler'],
        ],
        'content' => \Granola\Component::get('element', [
            'el' => 'span',
            'classes' => ['map__filters__toggler__label', 'map__filters__toggler__label__open'],
            'content' =>  \Granola\Component::get('element', [
                'content' => '',
                'el' => 'span',
                'allow_empty' => true,
                'classes' => [
                    'map__icon',
                    'map__icon--filter'
                ]
            ]) . \Granola\Component::get('element', [
                'content' => \__('Filter Plants', 'granola'),
                'el' => 'span',
            ])
        ]) . \Granola\Component::get('element', [
            'el' => 'span',
            'classes' => ['map__filters__toggler__label', 'map__filters__toggler__label__close'],
            'content' =>  \Granola\Component::get('element', [
                'content' => '',
                'el' => 'span',
                'allow_empty' => true,
                'classes' => [
                    'map__icon',
                    'map__icon--filter--close'
                ]
            ]) . \Granola\Component::get('element', [
                'content' => \__('Close Filter', 'granola'),
                'el' => 'span',
            ])
        ])
    ];

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
