<?php

namespace Granola\Components\HeroHeader;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'hero-header',
        'wp-block',
    ], $args['classes']);

    if (!empty($args['heading'])) {
        $args['heading'] = [
            'content' => $args['heading'],
            'classes' => ['hero-header__heading'],
        ];
    }

    if (!empty($args['strapline'])) {
        $args['strapline'] = [
            'content' => $args['strapline'],
            'classes' => ['hero-header__strapline'],
        ];

        if (!empty($args['image'])) {
            $args['attributes']['style']['--hero-header--image'] = 'url(' . $args['image']['url'] . ')';
        }
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
