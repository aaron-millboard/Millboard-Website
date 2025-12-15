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
            'el' => 'h1',
        ];

        if (!empty($args['image'])) {
            $args['attributes']['style']['--hero-header--image'] = 'url(' . $args['image']['url'] . ')';
        }
    } elseif (!empty($args['heading'])) {
        // Make heading <h1> if strapline not set.
        $args['heading']['el'] = 'h1';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
