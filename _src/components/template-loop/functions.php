<?php

namespace Granola\Components\TemplateLoop;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'items' => [],
        'object' => \Granola\WordPress\PageObject::get(),
        'items_component_args' => [],
    ], $args);

    if (empty($args['items'])) {
        while (\have_posts()) {
            \the_post();
            $args['items'][]['object'] = \get_post();
        }
    }

    // Fill items into items component args.
    $args['items_component_args']['items'] = $args['items'];

    // Set limit to 12
    $args['items_component_args']['limit'] = 12;

    // Set columns to 3
    $args['items_component_args']['columns'] = 3;

    // Filterable items output component.
    $args['items_component'] = 'cards-automatic';

    // Filterable items output component arguments.
    $args['items_component_args'] = \apply_filters(
        'granola/components/template-loop/items-component/args',
        $args['items_component_args']
    );

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
