<?php

namespace Granola\Components\ListFeatures;

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
        'features' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'list-features',
        'wp-block',
        'alignfull',
        'has-brand-2-background-color',
        'has-background',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Bail early if no content
    // -------------------------------------------------------------------------
    if (empty($args['features'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Process feature icons
    // -------------------------------------------------------------------------
    if (!empty($args['features']) && is_array($args['features'])) {
        foreach ($args['features'] as $key => $feature) {
            if (!empty($feature['icon'])) {
                $args['features'][$key]['icon_data'] = [
                    'attachment_id' => $feature['icon'],
                    'size' => 'full',
                ];
            }
        }
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
