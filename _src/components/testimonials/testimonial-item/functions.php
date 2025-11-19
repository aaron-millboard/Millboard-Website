<?php

namespace Granola\Components\Testimonials\TestimonialItem;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'image' => null,
        'testimonial' => '',
        'name' => '',
        'affiliation' => '',
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'testimonial-item',
        'wp-block',
    ], $args['classes']);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['testimonial'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
