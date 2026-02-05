<?php

namespace Granola\Components\Map\Listing;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'link' => [],
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'map__listing',
    ], $args['classes']);

    if (empty($args['address'])) {
        return null;
    }

    $args['attributes']['data-map-item-lat'] = $args['address']['lat'];
    $args['attributes']['data-map-item-lng'] = $args['address']['lng'];

    // Finally set address.
    $args['address'] = $args['address']['address'];


    if (!empty($args['phone'])) {
        $args['phone'] = [
            'content' => $args['phone'],
            'url' => 'mailto:' . $args['phone'],
            'classes' => [
                'map__listing__phone',
            ],
        ];
    }

    if (!empty($args['website'])) {
        $args['link'] = [
            'content' => \__('Contact installer', 'granola'),
            'url' => $args['website'],
            'classes' => [
                'map__listing__link',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
