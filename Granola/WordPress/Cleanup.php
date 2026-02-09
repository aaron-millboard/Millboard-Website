<?php

namespace Granola\WordPress;

class Cleanup
{
    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'head_cleanup']);
        \add_action('wp_footer', [__CLASS__, 'no_embed']);

        // Prevent recent comments widget css being output.
        \add_filter('show_recent_comments_widget_style', '__return_false');

        // ---------------------------------------
        // Emoji Cleanup.
        // ---------------------------------------
        \add_action('init', [__CLASS__, 'disable_emoji']);
        \add_filter('emoji_svg_url', '__return_false');
        \add_filter('tiny_mce_plugins', [__CLASS__, 'disable_emoji_tiny_mce']);

        // Don't convert :) to an emoji image.
        \remove_filter('the_content', [__CLASS__, 'convert_smilies'], 20);

        // ---------------------------------------
        // Gutenberg duotone SVG Cleanup.
        // ---------------------------------------
        \remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
        \remove_action('in_admin_header', 'wp_global_styles_render_svg_filters');

        // ---------------------------------------
        // Remove 'Customise' link from Admin Bar.
        // Priority 250 is after all menus are registered.
        // ---------------------------------------
        \add_action('admin_bar_menu', [__CLASS__, 'admin_bar_customise_clean_up'], 250);

        // ---------------------------------------
        // Hide ACF from Admin Sidebar Menu.
        // ---------------------------------------
        \add_filter('acf/settings/show_admin', [__CLASS__, 'hide_acf_on_production']);

        // ---------------------------------------
        // Cleanup Admin Dashboard.
        // ---------------------------------------
        \add_action('wp_dashboard_setup', [__CLASS__, 'clean_up_admin_dashboard']);

        // ---------------------------------------
        // Turn off admin email check on development
        // ---------------------------------------
        \add_action('admin_email_check_interval', [__CLASS__, 'turn_off_admin_email_check_development']);

        // ---------------------------------------
        // Removes the FSE "Design" page under Appearance
        // ---------------------------------------
        add_action('admin_menu', [__CLASS__, 'remove_fse_design_admin_page']);

        // ---------------------------------------
        // Removes the "Custom CSS" option in the Customizer.
        // ---------------------------------------
        \add_action('customize_register', [__CLASS__, 'remove_custom_css_option']);
    }

    /**
     * Deregisters unnecessary wp-embed script.
     */
    public static function no_embed(): void
    {
        \wp_deregister_script('wp-embed');
    }

    /**
     * Remove unnecessary actions from wp_head hook.
     */
    public static function head_cleanup(): void
    {
        // Remove EditURI link.
        \remove_action('wp_head', 'rsd_link');

        // Remove Windows live writer.
        \remove_action('wp_head', 'wlwmanifest_link');

        // Remove index link.
        \remove_action('wp_head', 'index_rel_link');

        // Remove previous link.
        \remove_action('wp_head', 'parent_post_rel_link');

        // Remove start link.
        \remove_action('wp_head', 'start_post_rel_link');

        // Remove links for adjacent posts.
        \remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');

        // Remove WP version.
        \remove_action('wp_head', 'wp_generator');
    }

    /**
     * Remove all actions related to emojis.
     */
    public static function disable_emoji(): void
    {
        \remove_action('admin_print_styles', 'print_emoji_styles');
        \remove_action('wp_head', 'print_emoji_detection_script', 7);
        \remove_action('admin_print_scripts', 'print_emoji_detection_script');
        \remove_action('wp_print_styles', 'print_emoji_styles');
        \remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        \remove_filter('the_content_feed', 'wp_staticize_emoji');
        \remove_filter('comment_text_rss', 'wp_staticize_emoji');
    }

    /**
     * Remove wpemoji plugin from TinyMCE editor.
     *
     * @param array $plugins An array of default TinyMCE plugins.
     */
    public static function disable_emoji_tiny_mce($plugins): array
    {
        if (!is_array($plugins)) {
            return [];
        }

        return array_diff($plugins, ['wpemoji']);
    }

    /**
     * Remove customise link from main admin bar.
     */
    public static function admin_bar_customise_clean_up(\WP_Admin_Bar $wp_admin_bar): void
    {
        $wp_admin_bar->remove_menu('customize');
    }

    /**
     * Hide ACF from Admin Sidebar Menu.
     */
    public static function hide_acf_on_production(): bool
    {
        // Explicitly allow ACF settings on '.test' URLs.
        if (str_ends_with(\get_site_url(), '.test')) {
            return true;
        }

        // Fallback - check against environment type and hide on production.
        return \wp_get_environment_type() !== 'production';
    }

    /**
     * Remove unrequired dashboard widgets.
     */
    public static function clean_up_admin_dashboard()
    {
        // WordPress blog - remove
        \remove_meta_box('dashboard_primary', 'dashboard', 'side');
        // Other WordPress News
        \remove_meta_box('dashboard_secondary', 'dashboard', 'side');
        // At a glance
        \remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
        // Yoast SEO preview
        \remove_meta_box('wpseo-dashboard-overview', 'dashboard', 'normal');
    }

    /**
     * Turn off admin email check.
     */
    public static function turn_off_admin_email_check_development()
    {
        if (\wp_get_environment_type() === 'development') {
            return '__return_false';
        }
    }

    /**
     * Removes the FSE "Design" page under Appearance.
     *
     * @return void
     */
    public static function remove_fse_design_admin_page(): void
    {
        \remove_submenu_page('themes.php', 'site-editor.php');
    }

    /**
     * Removes the "Custom CSS" option in the Customizer.
     *
     * @param WP_Customize_Manager $wp_customize
     * @return void
     */
    public static function remove_custom_css_option(\WP_Customize_Manager $wp_customize): void
    {
        $wp_customize->remove_control('custom_css');
    }
}
