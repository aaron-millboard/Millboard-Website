<?php

namespace Granola\Components\ListValues;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [],
        'preheading' => '',
        'heading' => '',
        'values' => [],
        'background' => 'none',
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'list-values',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Bail early if no content
    // -------------------------------------------------------------------------
    if (empty($args['values'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Background color
    // -------------------------------------------------------------------------
    if (!empty($args['background']) && $args['background'] !== 'none') {
        $args['classes'][] = 'has-' . $args['background'] . '-background-color';
        $args['classes'][] = 'has-background12';
    }

    // -------------------------------------------------------------------------
    // Process values items
    // -------------------------------------------------------------------------
    if (!empty($args['values']) && is_array($args['values'])) {
        foreach ($args['values'] as $key => $value) {
            // Ensure each value has the icon SVG
            $args['values'][$key]['icon'] = \Granola\SVG::get('icons-custom/pencil.svg');
        }
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
