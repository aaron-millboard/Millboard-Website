<?php

namespace Granola\Components\EmailSubscription;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [],
        'preheading' => '',
        'heading' => '',
        'image' => '',
        'gravity_form_id' => '',
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'email-subscription',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Gravity Form ID
    // -------------------------------------------------------------------------
    if (empty($args['gravity_form_id'])) {
        $args['gravity_form_id'] = \get_field('gravity_form_id', 'options') ?: null;

        if (empty($args['gravity_form_id'])) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Process image
    // -------------------------------------------------------------------------
    if (!empty($args['image'])) {
        $args['image_data'] = [
            'attachment_id' => $args['image'],
            'size' => 'large'
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
