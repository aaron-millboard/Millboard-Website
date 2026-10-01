<?php

namespace Granola\Components\AdviceJourneyNav;

use Theme\Utils\Advice;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'heading' => null,
        'intro' => null,
        'stages' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'advice-journey-nav',
        'wp-block',
    ], $args['classes']);

    $args['stages'] = build_stages((array) $args['stages']);

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * The stage rows, numbered and counted.
 *
 * The number is the row's position, not a field: reorder the rows and the
 * numbers follow, rather than leaving a "03" above an "02".
 *
 * Shared with the schema block, which lists the same rows.
 */
function build_stages(array $rows): array
{
    $rows = array_values(array_filter($rows, function ($row) {
        return !empty($row['title']);
    }));

    return array_map(function ($row, $index) {
        $terms = array_map('intval', (array) ($row['categories'] ?? []));
        $count = $terms ? Advice::guide_count($terms) : 0;

        return [
            'number' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
            'title' => $row['title'],
            'description' => $row['description'] ?? '',
            'link' => !empty($row['link']['url']) ? $row['link'] : null,
            // No count rather than "0 guides": a stage with nothing in it yet
            // is still a stage, and a zero reads as a broken section.
            'count' => $count ? Advice::guide_label($count) : '',
            'delay' => $index * 120,
        ];
    }, $rows, array_keys($rows));
}
