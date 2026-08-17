<?php

namespace Granola\Components\InstallerProjects;

function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'link' => null,
        'featured_label' => '',
        'featured_image' => null,
        'featured_title' => '',
        'featured_subtitle' => '',
        'projects' => [],
    ], $args);

    $args['classes'] = array_merge([
        'installer-projects',
        'wp-block',
    ], $args['classes']);

    $args['projects'] = array_values(array_filter((array) $args['projects'], function ($row) {
        return !empty($row['image']) || !empty($row['title']);
    }));

    $args['has_featured'] = !empty($args['featured_image']);

    // Bail early - return null for no output (unless previewing in the editor).
    if (!$args['has_featured'] && empty($args['projects']) && empty($args['is_preview'])) {
        return null;
    }

    return $args;
}
