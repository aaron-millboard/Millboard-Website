<?php

namespace Granola\Components\InstallerRanges;

function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'link' => null,
        'ranges' => [],
    ], $args);

    $args['classes'] = array_merge([
        'installer-ranges',
        'wp-block',
    ], $args['classes']);

    $args['ranges'] = array_values(array_filter((array) $args['ranges'], function ($row) {
        return !empty($row['name']) || !empty($row['image']);
    }));

    // Bail early - return null for no output (unless previewing in the editor).
    if (empty($args['ranges']) && empty($args['is_preview'])) {
        return null;
    }

    return $args;
}
