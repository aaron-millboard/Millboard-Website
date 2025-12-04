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

    // Pass post type
    // check if archive page
    if (is_archive()) {
        $post_type = get_queried_object()->name;
    } else {
        $post_type = get_post_type($args['object']);
    }
    $args['items_component_args']['post_type'] = $post_type;


    // Set limit to 12
    $args['items_component_args']['limit'] = 12;

    // Set columns to 3
    $args['items_component_args']['columns'] = 3;

    // Set columns to 2 for case studies
    if ($post_type === 'case-study') {
        $args['items_component_args']['columns'] = 2;
    }

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
