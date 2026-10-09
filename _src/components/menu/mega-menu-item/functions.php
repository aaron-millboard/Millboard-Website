<?php

namespace Granola\Components\Menu\MegaMenuItem;

/**
 * One entry in a mega menu panel, in one of three shapes.
 *
 *   card  a tall picture tile with the title under it, for a product range
 *   rail  a single line with a chevron, for the grouped links beside the cards
 *   text  a title with a sentence under it, for the text-only panels
 *
 * The shape is passed in by mega-menu-list, which decides it from the menu's
 * content. Nothing about an item changes between shapes except how it is drawn.
 */
function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'item' => null,
        'shape' => 'text',
        'depth' => 0,
        'row_index' => null,
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['item']) || !\is_object($args['item'])) {
        return null;
    }

    $item = $args['item'];

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'mega-menu-item',
        'mega-menu-item--' . $args['shape'],
        'menu-item',
    ], $args['classes'], $item->classes ?? []);

    $args['classes'][] = 'menu-item--depth-' . $args['depth'];

    $args['attributes']['id'] = 'menu-item-' . $item->ID;

    // ---------------------------------------
    // Where this row sits in the panel, counted straight through.
    //
    // The drawer staggers a pane's rows as they arrive, and nth-child cannot
    // do the counting: a panel is several lists -- the cards, then a list per
    // group -- so nth-child restarts at every one of them and the fifth row on
    // screen was arriving at the same moment as the first. mega-menu-list
    // counts once, in the order it prints, and hands each row its place.
    //
    // Only the drawer reads it; above the breakpoint the panel is already open
    // by the time it is seen and nothing is staggered.
    // ---------------------------------------
    if ($args['row_index'] !== null) {
        $args['attributes']['style']['--mbh-drawer--row-index'] = (int) $args['row_index'];
    }

    // ---------------------------------------
    // The picture, for a card.
    //
    // mega-menu-list has already resolved this to an id where it found one, so
    // a card never has to ask ACF a second time. A card without a picture is
    // still drawn, with the tile's own background standing in, because a
    // missing image should not take the link away.
    // ---------------------------------------
    $image = $item->mega_image ?? null;
    if (!$image && \function_exists('get_field')) {
        $image = \get_field('mega_menu_item_image', $item);
        if (\is_array($image)) {
            $image = $image['ID'] ?? null;
        }
    }
    $args['item_image'] = \is_numeric($image) ? (int) $image : null;

    if ($args['item_image']) {
        $args['classes'][] = 'mega-menu-item--has-image';
    }

    // ---------------------------------------
    // The link.
    // ---------------------------------------
    $args['link'] = [
        'url' => $item->url,
        'title' => $item->title,
        'target' => $item->target ?: null,
        'attr_title' => $item->attr_title ?: null,
    ];

    // Only the text shape draws a description, so the others do not carry one.
    $args['description'] = ($args['shape'] === 'text' && !empty($item->description))
        ? $item->description
        : null;

    // Current menu item classes.
    if ($item->is_current_item ?? false) {
        $args['classes'][] = 'menu-item--current';
    }

    if ($item->is_current_parent ?? false) {
        $args['classes'][] = 'current-menu-parent';
    }

    if ($item->is_current_ancestor ?? false) {
        $args['classes'][] = 'current-menu-ancestor';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
