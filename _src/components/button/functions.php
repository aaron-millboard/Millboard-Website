<?php

namespace Granola\Components\Button;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'el' => 'button',
        'content' => '',
        'classes' => [],
        'visually_hidden_text' => false,
        'attributes' => [],
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['content'])) {
        return null;
    }

    if (!empty($args['visually_hidden_text'])) {
        $args['content'] = \Granola\Component::get('element', [
            'content' => $args['content'],
            'classes' => [
                'visually-hidden',
            ],
        ]);
    }

    // Enforce button type.
    if (empty($args['type']) && empty($args['attributes']['type'])) {
        $args['attributes']['type'] = 'button';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
