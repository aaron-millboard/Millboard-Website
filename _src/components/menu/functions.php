<?php

namespace Granola\Components\Menu;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'menu_id' => null,
        'menu_class' => null,
        'classes' => [],
        'items' => [],
        'heading' => null,
        'max_depth' => null,
        'theme_location' => null,
        'heading_button' => false,
        'expandable_element_attributes' => [],
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['theme_location']) || !\has_nav_menu($args['theme_location'])) {
        return null;
    }

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'menu',
    ], $args['classes']);

    // Retrieve the given nav menu from the list of all locations.
    $locations = \get_nav_menu_locations();
    $menu_id = $locations[$args['theme_location']];

    // Retrieve the nav menu's items (and prevent unnecessary automatic sort).
    $unsorted_items = \wp_get_nav_menu_items($menu_id, [
        'output' => false,
    ]);

    /**
     * First, create an array of all menu items sorted by their own IDs.
     * This array can then be referenced later to find parent/ancestor items.
     */
    $menu_items = [];
    foreach ($unsorted_items as $item) {
        $menu_items[$item->ID] = $item;
    }

    /**
     * Second, create an array of all menu items sorted & grouped by their parents' IDs.
     * This array can then be referenced later to find each menu item's children.
     * Additionally, update any parent/ancestor items of the current page's menu item.
     */
    $sorted_items = [];
    foreach ($menu_items as &$item) {
        $sorted_items[$item->menu_item_parent]['children'][] = $item;

        // Additional data for current page item.
        if (is_menu_item_current_page($item)) {
            $item->is_current_item = true;

            // Determine the parent item.
            $sorted_items[$item->menu_item_parent]['is_current_parent'] = true;

            // Determine any ancestor items.
            $parent = $menu_items[$item->menu_item_parent] ?? null;
            while (!empty($parent->menu_item_parent)) {
                $sorted_items[$parent->menu_item_parent]['is_current_ancestor'] = true;
                $parent = $menu_items[$parent->menu_item_parent] ?? null;
            }
        }
    }
    unset($item); // Pass by reference fix.

    /**
     * Finally, set each menu item's children and parent/ancestor status from the sorted array.
     * This creates the menu structure where each item has an array of child items, nesting downwards.
     *
     * Additionally, add any top level menu items to this component's 'items' arg to create the final menu array.
     */
    foreach ($menu_items as &$item) {
        $item->children = $sorted_items[$item->ID]['children'] ?? [];
        $item->is_current_parent = !empty($sorted_items[$item->ID]['is_current_parent']);
        $item->is_current_ancestor = !empty($sorted_items[$item->ID]['is_current_ancestor']);

        // WP stores this as a string "0", but some plugins (e.g. WPML) store this as an int.
        if ($item->menu_item_parent === '0' || $item->menu_item_parent === 0) {
            $args['items'][] = $item;
        }
    }
    unset($item); // Pass by reference fix.

    // Set menu heading from theme_location.
    if ($args['heading'] === true) {
        $args['heading'] = \wp_get_nav_menu_name($args['theme_location']);
    }

    // -------------------------------------------------------------------------
    // Heading button.
    // -------------------------------------------------------------------------
    if ($args['heading_button'] === true) {
        $args['button'] = [
            'content' => __('Expand menu for ' . $args['heading']),
            'visually_hidden_text' => true,
            'classes' => ['menu__heading__button', 'g-button', 'g-button--square', 'g-button--arrow'],
            'attributes' => [
                'aria-expanded' => 'false',
                'aria-controls' => 'menu-' . $args['theme_location'],
            ],
        ];

        $args['expandable_element_attributes'] = [
            'id' => 'menu-' . $args['theme_location'],
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Determines whether the given menu item matches the current page.
 *
 * @param object $item The menu item. Default this is a WP_Post, but plugins e.g. WPML may use an extended bespoke type.
 * @return boolean Whether the menu item matches the current page.
 */
function is_menu_item_current_page(object $item): bool
{
    // A custom menu item with a URL that matches the current URL.
    if ($item->type === 'custom') {
        return is_custom_menu_item_current_url($item);
    }

    // A post type archive menu item which matches the current post type archive.
    if ($item->type === 'post_type_archive') {
        return \is_post_type_archive($item->object);
    }

    // A taxonomy/term archive menu item which matches the current taxonomy/term archive.
    if ($item->type === 'taxonomy') {
        return \Granola\Helpers::is_taxonomy() && \get_queried_object()->taxonomy === $item->object;
    }

    // A post type menu item which matches the current queried object ID.
    if ($item->type === 'post_type' && (int) $item->object_id === \get_queried_object_id()) {
        return true;
    }

    // A Granola Template Page menu item - several variations.
    if ($item->type === 'post_type' && $item->object === 'granola-template') {
        $object = \Granola\WordPress\TemplatePage::get_templated_object(
            \get_post((int) $item->object_id)
        );

        if ($object instanceof \WP_Term && $object->term_id === \get_queried_object_id()) {
            return true;
        } elseif ($object instanceof \WP_Post_Type && \is_post_type_archive($object->name)) {
            return true;
        } elseif ($object instanceof \WP_Post_Type && \is_home() && $object->name === 'post') {
            return true;
        }
    }

    return false;
}

/**
 * Determines whether the given menu item has a custom URL which matches the current page.
 *
 * Much of this logic has been adapted from WP core.
 *
 * @see wp-includes/nav-menu-template.php
 *
 * @param object $item The menu item with a custom URL. Default this is a WP_Post, but plugins e.g. WPML may use an extended bespoke type.
 * @return boolean Whether the menu item's URL which matches the current page.
 */
function is_custom_menu_item_current_url(object $item): bool
{
    // Bail early - host not set for some reason.
    if (!isset($_SERVER['HTTP_HOST'])) {
        return false;
    }

    // Relative path for current URL.
    $current_path = \untrailingslashit($_SERVER['REQUEST_URI']);

    // Customize page: strip the query var from the URL before comparing.
    if (\is_customize_preview()) {
        $current_path = strstr($current_path, '?', true);
    }

    $current_url = \set_url_scheme('https://' . $_SERVER['HTTP_HOST'] . $current_path);

    // Retrieve current menu item URL and normalise.
    // Strip fragments, query args, and trailing slashes from item URL. Also ensure there's a scheme.
    $item_url = $item->url;
    $item_url = strpos($item_url, '#') ? strstr($item_url, '#', true) : $item_url;
    $item_url = strpos($item_url, '?') ? strstr($item_url, '?', true) : $item_url;
    $item_url = \set_url_scheme(\untrailingslashit($item_url));

    // WP core also checks for URLs with an 'index.php' filename.
    // This has been omitted as it is almost never needed.
    $matches = [
        $current_path,
        urldecode($current_path),
        $current_url,
        urldecode($current_url),
    ];

    if ($item_url && in_array($item_url, $matches, true)) {
        return true;
    } elseif (\is_front_page() && $item_url === \home_url()) {
        return true;
    }

    return false;
}
