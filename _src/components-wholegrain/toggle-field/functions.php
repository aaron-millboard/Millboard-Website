<?php

namespace Granola\Components\ToggleField;

function filter_args(array $args): array
{

    // Set up defaults
    $args = array_merge([
        'attributes' => [],
        'type' => 'checkbox',
        'id' => wp_unique_id('toggle-'),
        'classes' => [],
        'value' => 1,
        'label' => '',
    ], $args);

    $args['attributes'] = array_merge([
        'for' => $args['id'] . '-input',
        'aria-hidden' => 'true',
    ], $args['attributes']);

    $args['classes'] = array_merge([
        'toggle-field',
    ], $args['classes']);

    if (empty($args['name'])) {
        $args['name'] = $args['id'];
    }

    // -------------------------------------------------------------------------
    // Return the content to the render functions
    // -------------------------------------------------------------------------
    return $args;
}
