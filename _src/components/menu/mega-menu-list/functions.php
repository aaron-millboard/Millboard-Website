<?php

namespace Granola\Components\Menu\MegaMenuList;

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
        'widget' => null,
        'cta' => null,
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
        'mega-menu-list',
    ], $args['classes']);

    // Split items into columns
    // If widget is enabled, we have 2 columns for menu items
    // If no widget, we have 3 columns for menu items
    $total_items = count($args['items']);
    $has_widget = !empty($args['widget']);
    $num_columns = $has_widget ? 2 : 3;

    if ($has_widget) {
        $args['classes'][] = 'mega-menu-list--has-widget';
    }

    // Calculate items per column
    $items_per_column = ceil($total_items / $num_columns);

    $args['columns'] = [];
    $column_index = 0;

    foreach ($args['items'] as $index => $item) {
        if ($index > 0 && $index % $items_per_column === 0) {
            $column_index++;
        }
        $args['columns'][$column_index][] = $item;
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
