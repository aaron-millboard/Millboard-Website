<?php

namespace Granola\Components\Heading;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'el' => 'h2',
        'content' => '',
        'link' => null,
        'target' => null,
        'attributes' => [],
        'classes' => [],
    ], $args);

    // Wrap the heading content in a link if one is provided.
    if (!empty($args['link'])) {
        $args['content'] = \Granola\Component::get('link', [
            'content' => $args['content'],
            'url' => $args['link'],
            'target' => $args['target'] ?? null,
        ]);
    }

    if (!empty($args['id'])) {
        $args['attributes']['id'] = $args['id'];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
