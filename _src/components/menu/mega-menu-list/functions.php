<?php

namespace Granola\Components\Menu\MegaMenuList;

/**
 * Sort a mega menu's children into the three shapes the panel can draw.
 *
 * The design has two panels, and which one a menu gets is decided by its own
 * content rather than by a setting someone has to remember to tick:
 *
 *   cards  a child with a picture and no children of its own. These are the
 *          product ranges, and they get the tall image tiles on the left.
 *   groups a child that has children. Its title becomes a heading in the rail
 *          on the right and its children become the links under it.
 *   links  everything else: a title and a description, which is the text-only
 *          panel Resources, About us and Find & buy get.
 *
 * A menu with any cards draws the cards panel and puts its groups and loose
 * links in the rail. A menu with none draws the text panel. That means the
 * menus as they stand today already render: decking and cladding have a
 * picture on every child, the other four have descriptions and no pictures.
 *
 * Nothing here requires the menus to be restructured. Nesting a level deeper
 * turns a flat list into headed groups, which is how the design draws
 * "Components" and "Before you buy", but a flat menu still renders correctly.
 */
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

    // ---------------------------------------
    // Sort the children.
    // ---------------------------------------
    $cards = [];
    $groups = [];
    $links = [];

    foreach ($args['items'] as $item) {
        $has_children = !empty($item->children);
        $image = \function_exists('get_field') ? \get_field('mega_menu_item_image', $item) : null;

        // ACF hands an image back as an id, an array or a URL depending on the
        // field's return format, and only an id is useful to the renderer.
        if (\is_array($image)) {
            $image = $image['ID'] ?? null;
        }
        $image = \is_numeric($image) ? (int) $image : null;

        if ($has_children) {
            $groups[] = ['item' => $item, 'links' => $item->children];
            continue;
        }

        if ($image) {
            $item->mega_image = $image;
            $cards[] = $item;
            continue;
        }

        $links[] = $item;
    }

    $args['cards'] = $cards;
    $args['groups'] = $groups;
    $args['links'] = $links;

    // The picture panel, or the text one.
    $args['has_cards'] = !empty($cards);
    $args['classes'][] = $args['has_cards']
        ? 'mega-menu-list--cards'
        : 'mega-menu-list--text';

    // With cards, anything that is not a card belongs in the rail beside them.
    // Without cards there is no rail: the links are the panel.
    //
    // A call to action on its own does not earn a rail. The menus today have
    // no grouped children and a long CTA label, and reserving the 260px column
    // for it alone put a wrapped button in a tall empty margin. The CTA sits
    // under the panel instead, where it has the full width.
    $args['has_rail'] = $args['has_cards'] && ($groups || $links);
    if ($args['has_rail']) {
        $args['classes'][] = 'mega-menu-list--has-rail';
    }

    if (!empty($args['widget'])) {
        $args['classes'][] = 'mega-menu-list--has-widget';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
