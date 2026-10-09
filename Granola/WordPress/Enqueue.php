<?php

namespace Granola\WordPress;

class Enqueue
{
    public static function init(): void
    {
        \add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_main_assets']);
        \add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_comment_assets']);
        \add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        \add_action('enqueue_block_editor_assets', [__CLASS__, 'enqueue_editor_assets']);

        // WP global styles need to be dequeued in both head and footer.
        \add_action('wp_enqueue_scripts', [__CLASS__, 'dequeue_wp_global_styles']);
        \add_action('wp_footer', [__CLASS__, 'dequeue_wp_global_styles']);

        // Ensure WP core block styles are all lumped into the block library stylesheet (which we dequeue).
        \add_filter('should_load_separate_core_block_assets', '__return_false');
        \add_filter('should_load_block_assets_on_demand', '__return_true');

        \add_action('wp_enqueue_scripts', [__CLASS__, 'dequeue_wp_block_library_styles']);

        \add_action('wp_default_scripts', [__CLASS__, 'move_jquery_to_footer']);
        \add_action('wp_default_scripts', [__CLASS__, 'remove_jquery_migrate']);

        \add_filter('style_loader_src', [__CLASS__, 'remove_asset_version']);
        \add_filter('script_loader_src', [__CLASS__, 'remove_asset_version']);

        \add_filter('granola/scripts/dependencies', [__CLASS__, 'add_jquery_dependency']);
        \add_filter('granola/scripts/localization', [__CLASS__, 'add_ajax_localization']);
    }

    /**
     * Enqueue Granola block editor assets.
     */
    public static function enqueue_editor_assets(): void
    {
        // ------------------------------------------
        // Editor Scripts
        // ------------------------------------------
        \wp_enqueue_script(
            'granola-editor',
            \Granola\Asset::URL('editor.js', true),
            \apply_filters('granola/scripts/editor/dependencies', ['wp-blocks', 'wp-dom']),
            '',
            true
        );
    }

    /**
     * Enqueue Granola admin assets.
     */
    public static function enqueue_admin_assets(): void
    {
        // ------------------------------------------
        // Admin Scripts
        // ------------------------------------------
        \wp_enqueue_script(
            'granola-admin',
            \Granola\Asset::URL('admin.js', true),
            \apply_filters('granola/scripts/admin/dependencies', []),
            '',
            true
        );

        // ------------------------------------------
        // Admin Styles
        // ------------------------------------------
        \wp_enqueue_style(
            'granola-admin',
            \Granola\Asset::URL('admin.css', true),
            \apply_filters('granola/styles/admin/dependencies', []),
            false
        );
    }

    /**
     * Remove file version query argument from all enqueued styles and scripts.
     *
     * @param string $src The source URL of the enqueued asset.
     * @return string The filtered URL of the enqueued asset.
     */
    public static function remove_asset_version(string $src): string
    {
        if (strpos($src, '?ver=')) {
            $src = \remove_query_arg('ver', $src);
        }

        return $src;
    }

    /**
     * Enqueue all main Granola assets - styles & scripts
     */
    public static function enqueue_main_assets(): void
    {
        // ------------------------------------------
        // Enqueue Granola CSS
        // ------------------------------------------
        \wp_enqueue_style(
            'granola-styles',
            \Granola\Asset::URL('main.css', true),
            \apply_filters('granola/styles/dependencies', []),
            false
        );


        // ------------------------------------------
        // Enqueue Granola Print CSS
        // ------------------------------------------
        // \wp_enqueue_style(
        //     'granola-print',
        //     \Granola\Asset::URL('print.css', true),
        //     \apply_filters('granola/print/dependencies', []),
        //     false,
        //     'print'
        // );

        // ------------------------------------------
        // Register Granola JS
        // ------------------------------------------
        \wp_register_script(
            'granola-scripts',
            \Granola\Asset::URL('main.js', true),
            \apply_filters('granola/scripts/dependencies', []),
            '',
            true
        );

        // ------------------------------------------
        // Define Granola JS localization.
        // Allows passing PHP variables to JS.
        // ------------------------------------------
        \wp_localize_script(
            'granola-scripts',
            'params',
            \apply_filters('granola/scripts/localization', [])
        );

        // ------------------------------------------
        // Enqueue Granola JS
        // ------------------------------------------
        \wp_enqueue_script('granola-scripts');
    }

    /**
     * Conditionally enqueue WP comment-reply JS.
     */
    public static function enqueue_comment_assets(): void
    {
        if (\Granola\WordPress\Comments::enqueue_reply_script()) {
            \wp_enqueue_script('comment-reply');
        }
    }

    /**
     * Conditionally dequeue WP's core global styling inline css.
     *
     * Dequeues: global-styles-inline-css
     */
    public static function dequeue_wp_global_styles(): void
    {
        if (\apply_filters('granola/config/remove_wp_global_styles', false)) {
            \wp_dequeue_style('global-styles');
        }
    }

    /**
     * Conditionally dequeue WP's block library stylesheet.
     *
     * Dequeues: /wp-includes/css/dist/block-library/style.min.css
     */
    public static function dequeue_wp_block_library_styles(): void
    {
        if (\apply_filters('granola/config/remove_wp_block_library_styles', false)) {
            \wp_dequeue_style('wp-block-library');
            \wp_dequeue_style('wp-block-library-theme');
        }
    }

    /**
     * Removes the jQuery Migrate script bundled in WordPress core.
     */
    public static function remove_jquery_migrate(&$scripts): void
    {
        if (\is_admin()) {
            return;
        }

        if (\apply_filters('granola/config/remove_jquery_migrate', false)) {
            $scripts->remove('jquery');
            $scripts->add('jquery', false, array('jquery-core'), '1.12.4');
        }
    }

    /**
     * Moves jQuery to the footer unless it's required in the header.
     *
     * Places jQuery <script> in the footer by default. However, if a plugin requires it in
     * the header, it will automatically be moved there.
     *
     * @link https://wordpress.stackexchange.com/questions/173601/enqueue-core-jquery-in-the-footer/240612#240612
     */
    public static function move_jquery_to_footer($wp_scripts): void
    {
        if (\is_admin()) {
            return;
        }

        if (\apply_filters('granola/config/jquery_in_footer', false)) {
            $wp_scripts->add_data('jquery', 'group', 1);
            $wp_scripts->add_data('jquery-core', 'group', 1);
        }
    }

    /**
     * Adds AJAX object properties to granola-scripts via localization if required via config.
     *
     * @link https://developer.wordpress.org/reference/functions/wp_localize_script/
     *
     * @param array $localizations An array of 'localizations' for granola-scripts.
     * @return array The filtered array of localizations for granola-scripts, with AJAX values conditionally added.
     */
    public static function add_ajax_localization($localizations): array
    {
        if (\apply_filters('granola/config/ajax_required', false)) {
            $localizations['ajax_url'] = \admin_url('admin-ajax.php');
            $localizations['home_url'] = \home_url();
        }

        return $localizations;
    }

    /**
     * Adds jQuery as a dependancy to granola-scripts if required via config.
     *
     * @param array $dependencies An array of dependencies for granola-scripts.
     * @return array The filtered array of dependencies for granola-scripts, with jQuery conditionally added.
     */
    public static function add_jquery_dependency($dependencies): array
    {
        if (\apply_filters('granola/config/jquery_required', false)) {
            $dependencies[] = 'jquery';
        }

        return $dependencies;
    }
}
