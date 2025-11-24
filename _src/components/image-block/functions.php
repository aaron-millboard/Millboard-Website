<?php

namespace Granola\Components\ImageBlock;

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
        'image-block',
        'wp-block',
    ], $args['classes']);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------

    if (empty($args['image'])) {
        return null;
    }

    $args['image'] = [
        'attachment_id' => $args['image'],
    ];


    if (!empty($args['heading'])) {
        $args['heading'] = [
            'content' => $args['heading'],
            'classes' => ['image-block__heading' , 'is-style-typestyle-h3', 'is-style-typestyle-uppercase'],
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
