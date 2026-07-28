<?php

namespace Granola\Components\InstallerServices;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'intro' => '',
        'services' => [],
        'cta_heading' => '',
        'cta_text' => '',
        'cta_link' => null,
        'cta_style' => 'light',
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'installer-services',
        'wp-block',
    ], $args['classes']);

    $args['services'] = array_values(array_filter((array) $args['services'], function ($row) {
        return !empty($row['title']) || !empty($row['image']) || !empty($row['description']);
    }));

    $args['has_cta'] = !empty($args['cta_heading']);
    if (empty($args['cta_style'])) {
        $args['cta_style'] = 'light';
    }

    // -------------------------------------------------------------------------
    // Bail early - return null for no output (unless previewing in the editor).
    // -------------------------------------------------------------------------
    if (empty($args['services']) && !$args['has_cta'] && empty($args['is_preview'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
