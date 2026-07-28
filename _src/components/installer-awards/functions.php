<?php

namespace Granola\Components\InstallerAwards;

function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'awards' => [],
    ], $args);

    $args['classes'] = array_merge([
        'installer-awards',
        'wp-block',
    ], $args['classes']);

    $args['awards'] = array_values(array_filter((array) $args['awards'], function ($row) {
        return !empty($row['title']) || !empty($row['year']);
    }));

    // Bail early - return null for no output (unless previewing in the editor).
    if (empty($args['awards']) && empty($args['is_preview'])) {
        return null;
    }

    return $args;
}
