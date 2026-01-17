<?php

namespace Granola\Components\Map\Filters;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [
            'aria-hidden' => 'false',
            'id' => 'map-filter',
        ],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'map__filters',
    ], $args['classes']);

     // -------------------------------------------------------------------------
    // Filters
    // -------------------------------------------------------------------------
    $args['filters'] = [
        [
            'id' => 'installer-type',
            'icons' => false,
            'label' => \__('Filter', 'granola'),
            'facets' => [
                [
                    'slug' => 'approved-installer-decking',
                    'label' => 'Approved Installer (Decking)',
                ],
                [
                    'slug' => 'approved-installer-cladding',
                    'label' => 'Approved Installer (Cladding)',
                ],
                [
                    'slug' => 'advanced-installer',
                    'label' => 'Advanced installer',
                ],
            ],
        ],
    ];

    $args['buttons']['clear'] = [
        'content' => \Granola\Component::get('element', [
            'content' => __('Clear all', 'granola'),
            'el' => 'span',
        ]),
        'classes' => [
            'map__filters__buttons__clear',
            'g-button',
        ],
    ];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Gets and sets a transient list of countries ordered by the number of plants per countries.
 * Also handles the UK being brought to the front of the list
 *
 * @param array $countries - an array of country names to 2 letter codes provided by a mapping function.
 * @return array $countries_counts - array of countries ordered by number of plants.
 */
function order_countries_by_totals($countries): array
{
    // No transient so create one.
    // $plants_data = \Granola\Components\Map\get_plants_data();
    $countries_counts = [];

    if (!empty($plants_data['data']) && !empty($plants_data['data']['plants'])) {
        // Loop over plants and count totals per country.
        foreach ($plants_data['data']['plants'] as $key => $plant) {
            // First check if we want to include this country (based on the countries passed in).
            // Which are the filters.
            if (isset($countries[$plant['country']])) {
                if (!isset($countries_counts[$plant['country']])) {
                    $countries_counts[$plant['country']] = 1;
                } else {
                    $countries_counts[$plant['country']] += 1;
                }
            }
        }
    }

    // Sort by total plants.
    arsort($countries_counts);

    // Move UK to the top of the pile.
    $uk_key = 'uk';
    $uk_values = $countries_counts[$uk_key] ?? [];
    unset($countries_counts[$uk_key]); // Remove UK.
    $countries_counts = [$uk_key => $uk_values] + $countries_counts; // Create new array with UK at front.

    return $countries_counts;
}
