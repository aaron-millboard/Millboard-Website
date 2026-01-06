<?php

namespace Granola\Components\PostSummaries;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'post-summaries',
        'wp-block',
    ], $args['classes']);

    if (!empty($args['heading'])) {
        $args['heading'] = [
            'content' => $args['heading'],
            'classes' => ['post-summaries__heading'],
        ];
    }

    if (\is_search()) {
        $query = \Granola\WordPress\PageObject::get();

        if (!empty($query->query['s'])) {
            $args['heading'] = [
                'content' => sprintf(
                    \_n(
                        // translators: 1: quantity of comments. 2: post title.
                        'Displaying %1$s search result for \'%2$s\'',
                        'Displaying %1$s search results for \'%2$s\'',
                        $query->found_posts,
                        'granola'
                    ),
                    \number_format_i18n($query->found_posts),
                    $query->query['s']
                ),
                'classes' => [
                    'post-summaries__heading',
                    'is-style-typestyle-h6',
                ],
            ];
        }
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
