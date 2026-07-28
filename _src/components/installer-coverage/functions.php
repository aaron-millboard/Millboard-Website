<?php

namespace Granola\Components\InstallerCoverage;

function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'intro' => '',
        'map_type' => 'embed',
        'map_side' => 'left',
        'map_embed' => '',
        'map_image' => null,
        'display' => 'rows',
        'coverage' => [],
        'button' => null,
    ], $args);

    $args['classes'] = array_merge([
        'installer-coverage',
        'wp-block',
    ], $args['classes']);

    if (($args['map_side'] ?? 'left') === 'right') {
        $args['classes'][] = 'installer-coverage--map-right';
    }

    // Normalise coverage rows; split towns into a list for the chips display.
    $args['coverage'] = array_values(array_filter(array_map(function ($row) {
        $towns = isset($row['towns']) ? (string) $row['towns'] : '';
        $list = array_values(array_filter(array_map('trim', preg_split('/[,\x{00B7}\n]+/u', $towns) ?: [])));
        return [
            'county' => $row['county'] ?? '',
            'towns' => $towns,
            'towns_list' => $list,
        ];
    }, (array) $args['coverage']), function ($row) {
        return !empty($row['county']) || !empty($row['towns']);
    }));

    $args['has_map'] = ($args['map_type'] === 'embed' && !empty($args['map_embed']))
        || ($args['map_type'] === 'image' && !empty($args['map_image']));

    // Bail early - return null for no output (unless previewing in the editor).
    if (!$args['has_map'] && empty($args['coverage']) && empty($args['is_preview'])) {
        return null;
    }

    return $args;
}
