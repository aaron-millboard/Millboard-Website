<?php

namespace Granola\Components\Map\Listing;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'map__listing',
    ], $args['classes']);

    // \Granola\Debug::dump($args);

    $args['attributes']['data-map-item-lat'] = $args['address']['lat'];
    $args['attributes']['data-map-item-lng'] = $args['address']['lng'];

    // Finally set address.
    $args['address'] = $args['address']['address'];


    $args['link'] = [
        'content' => \__('Contact installer', 'granola'),
        'url' => $args['website'],
    ];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
