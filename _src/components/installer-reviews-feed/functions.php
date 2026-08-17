<?php

namespace Granola\Components\InstallerReviewsFeed;

function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'overall_rating' => '',
        'review_count' => '',
        'sources_label' => '',
        'link' => null,
        'reviews' => [],
    ], $args);

    $args['classes'] = array_merge([
        'installer-reviews-feed',
        'wp-block',
    ], $args['classes']);

    $args['reviews'] = array_values(array_filter((array) $args['reviews'], function ($row) {
        return !empty($row['quote']) || !empty($row['name']);
    }));

    // Bail early - return null for no output (unless previewing in the editor).
    if (empty($args['reviews']) && empty($args['is_preview'])) {
        return null;
    }

    return $args;
}
