<?php

namespace Granola\WordPress;

/**
 * Removes the default 'post' post type if the 'granola/config/deactivate_posts_post_type' filter returns true.
 */
class PostsPT
{
    public static function init(): void
    {
        \add_action('after_setup_theme', [__CLASS__, 'maybe_deactivate_posts']);
    }

    /**
     * Determine whether to deactivate posts and conditionally add relevant hooks.
     *
     * @return void
     */
    public static function maybe_deactivate_posts()
    {
        // Bail early - posts are not deactivated via filter.
        if (!\apply_filters('granola/config/deactivate_posts_post_type', false)) {
            return;
        }

        \add_action('admin_bar_menu', [__CLASS__, 'remove_default_post_type_add_new'], 80);
        \add_action('admin_menu', [__CLASS__, 'remove_default_post_type_menu_item']);
        \add_action('current_screen', [__CLASS__, 'redirect_posts_admin_page']);
        \add_action('init', [__CLASS__, 'unregister_taxonomies'], 10);
    }

    /**
     * Remove '+New Post' from admin bar.
     *
     * @param \WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance, passed by reference.
     * @return void
     */
    public static function remove_default_post_type_add_new(\WP_Admin_Bar $wp_admin_bar): void
    {
        $wp_admin_bar->remove_node('new-post');
    }

    /**
     * Remove 'Posts' menu item from the admin Side Menu.
     *
     * @return void
     */
    public static function remove_default_post_type_menu_item(): void
    {
        \remove_menu_page('edit.php');
    }

    /**
     * Redirect any user trying to access post related pages.
     *
     * @param \WP_Screen $screen Current WP_Screen object.
     * @return void
     */
    public static function redirect_posts_admin_page(\WP_Screen $screen): void
    {
        if ($screen->base === 'edit' && $screen->post_type === 'post') {
            \wp_redirect(\admin_url());
            exit;
        }
    }

    /**
     * Unregister all taxonomies for the default 'post' post type.
     *
     * @return void
     */
    public static function unregister_taxonomies(): void
    {
        foreach (\get_object_taxonomies('post') as $taxonomy) {
            \unregister_taxonomy_for_object_type($taxonomy, 'post');
        }
    }
}
