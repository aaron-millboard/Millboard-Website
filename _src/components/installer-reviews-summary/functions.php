<?php

namespace Granola\Components\InstallerReviewsSummary;

function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'overall_rating' => '',
        'review_count' => '',
        'sources_label' => '',
        'sources' => [],
    ], $args);

    $args['classes'] = array_merge([
        'installer-reviews-summary',
        'wp-block',
    ], $args['classes']);

    $args['sources'] = array_values(array_filter((array) $args['sources'], function ($row) {
        return !empty($row['name']);
    }));

    // Bail early - return null for no output (unless previewing in the editor).
    if ($args['overall_rating'] === '' && empty($args['sources']) && empty($args['is_preview'])) {
        return null;
    }

    return $args;
}
