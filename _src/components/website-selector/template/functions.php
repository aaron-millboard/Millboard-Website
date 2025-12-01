<?php

namespace Granola\Components\WebsiteSelector\Template;

function filter_args(array $args): ?array
{

    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'preheading' => '',
        'heading' => '',
        'columns' => [],
    ], $args);


    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'website-selector',
        'has-background',
        'has-brand-5-background-color',
    ], $args['classes']);

    // ---------------------------------------
    // Process columns images
    // ---------------------------------------
    if (!empty($args['columns'])) {
        foreach ($args['columns'] as $index => $column) {
            if (!empty($column['image'])) {
                $args['columns'][$index]['image_data'] = [
                    'attachment_id' => $column['image'],
                    'size' => 'medium'
                ];
            }
        }
    }

    return $args;
}
