<?php

namespace Granola\Components\Menu\MenuList;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'items' => [],
        'depth' => 0,
        'max_depth' => null,
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['items'])) {
        return null;
    }

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'menu-list',
    ], $args['classes']);

    if (!empty($args['id'])) {
        $args['attributes']['id'] = $args['id'];
    }

    $args['classes'][] = 'menu-list--depth-' . $args['depth'];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
