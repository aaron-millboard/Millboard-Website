<?php

namespace Granola\Components\Link;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'el' => 'a',
        'content' => '',
        'url' => '',
        'attributes' => [
            'rel' => [],
        ],
        'classes' => [],
    ], $args);

    $args['content'] = $args['content'] ?? $args['title'];

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['content'])) {
        return null;
    }

    if (!empty($args['url'])) {
        $args['attributes']['href'] = $args['url'];
    }

    // Handles ACF link field target value.
    if (!empty($args['target'])) {
        $args['attributes']['target'] = $args['target'];
    }

    // Conditionally add appropriate rel attribute.
    if (!empty($args['attributes']['target']) && $args['attributes']['target'] === '_blank') {
        $args['attributes']['rel'][] = 'noopener';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
