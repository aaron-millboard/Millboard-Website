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

    $args['items_component_args']['items'] = $args['items'];

    // Filterable items output component.
    $args['items_component'] = \apply_filters('granola/components/template-loop/items-component', 'post-summaries');

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
