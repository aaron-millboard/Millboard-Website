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
        'taxonomy' => 'category',
        'filter_label' => \__('Explore and filter all articles', 'granola'),
        'post_type' => null,
    ], $args);

    if (empty($args['items'])) {
        while (\have_posts()) {
            \the_post();
            $args['items'][]['object'] = \get_post();
        }
    }

    // Set default taxonomy filter arguments.
    $args['taxonomy_filters'] = [
        'label' => $args['filter_label'],
        'taxonomy' => $args['taxonomy'],
        'object' => $args['object'],
    ];

    // Fill items into items component args.
    $args['items_component_args']['items'] = $args['items'];
    $args['items_component_args']['wp_query'] = false;

    // Pass post type
    // Use provided post_type or detect automatically
    if (!empty($args['post_type'])) {
        $post_type = $args['post_type'];
    } else {
        $post_type = \get_post_type();
    }
    $args['items_component_args']['post_type'] = $post_type;

    // Set limit to 12
    $args['items_component_args']['limit'] = 12;

    // Set columns to 3 if not already set
    if (!isset($args['items_component_args']['columns'])) {
        $args['items_component_args']['columns'] = 3;
    }

    // Set columns to 2 for case studies
    if ($post_type === 'case-study') {
        $args['items_component_args']['columns'] = 2;
        $args['taxonomy_filters']['label'] = \__('Explore and filter all case studies', 'granola');
    } elseif ($post_type === 'product') {
        $args['items_component_args']['columns'] = 4;
        unset($args['taxonomy_filters']);
    }

    // Filterable items output component.
    $args['items_component'] = \apply_filters('granola/components/template-loop/items-component', 'cards-automatic');

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

/**
 * Filters what component should be used to display the template loop items on search pages.
 *
 * @param string $component The name of the component to filter.
 * @return string The filtered name of the component used to display posts.
 */
function filter_search_template_loop(string $component): string
{
    if (\is_search()) {
        return 'post-summaries';
    }

    return $component;
}
