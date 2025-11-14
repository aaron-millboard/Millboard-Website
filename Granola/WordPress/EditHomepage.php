<?php

namespace Granola\WordPress;

class EditHomepage
{
    /**
     * Adds an edit homepage link to the WP admin menu.
     *
     * @return void
     */
    public static function init(): void
    {
        /**
         * Add the homepage edit link via submenu filter.
         *
         * @see /Granola/WordPress/Admin.php
         */
        \add_filter('granola/wordpress/admin/submenu', [__CLASS__, 'add_homepage_edit_link']);
    }

    /**
     * Filters the global $submenu to add a homepage edit link to the WP admin bar.
     *
     * @param array $submenu An array of WP admin menu items.
     */
    public static function add_homepage_edit_link($submenu): array
    {
        // Bail early - no 'static' homepage.
        if (\get_option('show_on_front') !== 'page') {
            return $submenu;
        }

        $homepage_id = \get_option('page_on_front', 0);

        // Bail early - homepage not set.
        if (empty($homepage_id)) {
            return $submenu;
        }

        // Get page edit URL.
        $homepage_edit_link = \get_edit_post_link($homepage_id);

        // Bail early - no edit link found.
        if (empty($homepage_edit_link)) {
            return $submenu;
        }

        // Create edit link array.
        $edit_homepage_menu_array = [
            \__('Edit Homepage', 'granola'),
            'edit_pages',
            $homepage_edit_link,
        ];

        // Add edit link.
        $submenu['edit.php?post_type=page'][] = $edit_homepage_menu_array;

        return $submenu;
    }
}
