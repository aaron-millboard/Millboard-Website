<?php

namespace Granola\Components\HomeTrust;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'heading' => null,
        'embed' => null,
        'link' => [],
        'badges' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'home-trust',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Badges.
    //
    // A row with no image is an empty repeater row. Logos vary wildly in
    // aspect -- a stacked roundel against a wide wordmark -- so they are
    // capped on both axes rather than set to one height, which would make the
    // tall ones tower over the wide ones.
    // -------------------------------------------------------------------------
    $args['badges'] = array_values(array_filter((array) $args['badges'], function ($badge) {
        return !empty($badge['image']);
    }));

    $args['badges'] = array_map(function ($badge) {
        $badge['image']['size'] = 'medium';
        $badge['image']['classes'] = ['home-trust__badge-image'];

        return $badge;
    }, $args['badges']);

    // -------------------------------------------------------------------------
    // Link.
    // -------------------------------------------------------------------------
    if (!empty($args['link'])) {
        $args['link']['classes'][] = 'home-trust__link';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
