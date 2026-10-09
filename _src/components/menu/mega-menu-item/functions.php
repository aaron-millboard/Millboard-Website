<?php

namespace Granola\Components\Menu\MegaMenuItem;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'item' => null,
        'depth' => 0,
        'max_depth' => null,
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['item'])) {
        return null;
    }

    $item = $args['item'];

    if (!is_object($item)) {
        return null;
    }

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'mega-menu-item',
        'menu-item',
    ], $args['classes'], $item->classes);

    $args['classes'][] = 'menu-item--depth-' . $args['depth'];

    $args['attributes']['id'] = 'menu-item-' . $item->ID;

    // Get menu item image
    $item_image_id = \get_field('mega_menu_item_image', $item);
    if ($item_image_id) {
        $args['item_image'] = $item_image_id;
        $args['classes'][] = 'mega-menu-item--has-image';
    }

    $args['link'] = [
        'url' => $item->url,
        'content' => $item->title,
        'target' => $item->target ?: null,
        'classes' => ['mega-menu-item__link'],
        'attributes' => [
            'title' => $item->attr_title ?: null,
        ],
    ];

    // Add description if available
    if (!empty($item->description)) {
        $args['description'] = $item->description;
    }

    if (!empty($item->xfn)) {
        $args['link']['attributes']['rel'][] = $item->xfn;
    }

    // Current menu item classes
    if ($item->is_current_item ?? false) {
        $args['classes'][] = 'menu-item--current';
    }

    if ($item->is_current_parent ?? false) {
        $args['classes'][] = 'current-menu-parent';
    }

    if ($item->is_current_ancestor ?? false) {
        $args['classes'][] = 'current-menu-ancestor';
    }

    // Handle third level menu items (children of mega menu items)
    if (!empty($item->children) && (empty($args['max_depth']) || $args['depth'] + 1 < $args['max_depth'])) {
        $args['has_children'] = true;
        $args['classes'][] = 'mega-menu-item--has-children';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
