<?php

namespace Granola\Components\CommentsNavigation;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    // Comments aren't paged or there's only
    // one page.
    // ---------------------------------------
    if (!\get_option('page_comments', false) || \get_comment_pages_count() < 2) {
        return null;
    }

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'comments-navigation',
        'navigation'
    ], $args['classes']);

    if (empty($args['heading'])) {
        $args['heading'] = [
            'content' => \__('Comment navigation', 'granola'),
            'el' => 'h3',
            'classes' => [
                'visually-hidden',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
