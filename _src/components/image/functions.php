<?php

namespace Granola\Components\Image;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'alt'        => '',
        'size'       => 'medium_large',
        'attributes' => [],
        'loading'   => 'lazy',
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['attachment_id'])) {
        return null;
    }

    if (!empty($args['sizes']) && !is_array($args['sizes'])) {
        $args['attributes']['sizes'] = $args['sizes'];
    }

    // Allow media library alt text to be overwritten.
    if (isset($args['alt']) && is_string($args['alt'])) {
        $args['attributes']['alt'] = $args['alt'];

        if ($args['alt'] === '') {
            $args['attributes']['role'] = 'presentation';
        }
    }

    if (!empty($args['loading'])) {
        $args['attributes']['loading'] = $args['loading'];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
