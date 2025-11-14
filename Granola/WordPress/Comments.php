<?php

namespace Granola\WordPress;

/**
 * Removes all comment related functionality and admin views depending on the
 * 'granola/config/deactivate_comments' filter.
 */
class Comments
{
    public static function init(): void
    {
        \add_action('after_setup_theme', [__CLASS__, 'maybe_deactivate_comments']);
    }

    /**
     * Determine whether to deactivate comments and conditionally add relevant hooks.
     *
     * @return void
     */
    public static function maybe_deactivate_comments(): void
    {
        if (self::are_comments_enabled()) {
            return;
        }

        \add_action('admin_init', [__CLASS__, 'remove_admin_comment_metabox']);
        \add_action('admin_init', [__CLASS__, 'remove_pt_comment_support']);
        \add_action('current_screen', [__CLASS__, 'redirect_comment_admin_page']);

        \add_action('admin_menu', [__CLASS__, 'remove_admin_menu_comments_page']);

        // Priority 250 is after all menus are registered.
        \add_action('admin_bar_menu', [__CLASS__, 'remove_admin_bar_comments_links'], 250);

        // Close comments on the front-end.
        \add_filter('comments_open', '__return_false', 20);
        \add_filter('pings_open', '__return_false', 20);

        // Hide existing comments.
        \add_filter('comments_array', '__return_empty_array', 10);
    }

    /**
     * Redirect any user trying to access comments page.
     *
     * @param \WP_Screen $screen Current WP_Screen object.
     */
    public static function redirect_comment_admin_page(\WP_Screen $screen): void
    {
        if ($screen->base === 'edit-comments') {
            \wp_redirect(\admin_url());
            exit;
        }
    }

    /**
     * Remove comments metabox from dashboard.
     */
    public static function remove_admin_comment_metabox(): void
    {
        \remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    }

    /**
     * Disable support for comments and trackbacks in post types.
     */
    public static function remove_pt_comment_support(): void
    {
        foreach (\get_post_types() as $post_type) {
            if (\post_type_supports($post_type, 'comments')) {
                \remove_post_type_support($post_type, 'comments');
                \remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }

    /**
     * Remove comments page in admin menu.
     */
    public static function remove_admin_menu_comments_page(): void
    {
        \remove_menu_page('edit-comments.php');
    }

    /**
     * Remove comments links from admin bar.
     *
     * @param \WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance, passed by reference.
     */
    public static function remove_admin_bar_comments_links(\WP_Admin_Bar $wp_admin_bar): void
    {
        $wp_admin_bar->remove_menu('comments');
    }

    /**
     * Determine whether the WP core comment-reply script should be enqueued.
     *
     * @return bool True if the comment-reply script should be enqueued, false otherwise.
     */
    public static function enqueue_reply_script(): bool
    {
        return self::are_comments_enabled()
            && \is_singular()
            && \comments_open()
            && \get_option('thread_comments');
    }

    /**
     * Determine whether all comment related functionality should be enabled for WordPress.
     *
     * @return bool Whether all comment related functionality should be enabled.
     */
    public static function are_comments_enabled(): bool
    {
        return !\apply_filters('granola/config/deactivate_comments', false);
    }
}
