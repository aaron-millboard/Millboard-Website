<?php

/**
 * Registers 'Image' CPT & handles related functionality.
 */

namespace Theme\PostTypes;

class Image
{
    protected const SLUG = 'image';
    public const ARCHIVE_POSTS_PER_PAGE = 7;

    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'register_post_type']);
        \add_action('pre_get_posts', [__CLASS__, 'filter_archive_posts_per_page']);
        \add_filter('granola/templates/post-types', [__CLASS__, 'filter_granola_templates_post_types']);
        \add_action('template_redirect', [__CLASS__, 'redirect_single_cpt']);
        \add_filter('wp_robots', [__CLASS__, 'filter_gallery_robots_link']);
    }


    public static function redirect_single_cpt()
    {
        if (is_singular(self::SLUG)) {
            wp_redirect(get_post_type_archive_link(self::SLUG), 301);
            exit;
        }
    }

    public static function filter_archive_posts_per_page(\WP_Query $query): void
    {
        if (\is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->is_post_type_archive(self::SLUG)) {
            $query->set('posts_per_page', self::ARCHIVE_POSTS_PER_PAGE);
        }
    }

    /**
     * Register CPT.
     *
     * @link https://github.com/johnbillion/extended-cpts/wiki/Registering-Post-Types
     */
    public static function register_post_type(): void
    {
        if (!function_exists('register_extended_post_type')) {
            return;
        }

        \register_extended_post_type(self::SLUG, [
            // Core post type configuration.
            'public' => true,
            'has_archive' => true,
            'hierarchical' => false,
            'show_in_rest' => true,
            'rewrite' => [
                'slug' => 'gallery',
            ],
            'menu_position' => 25, // Below comments.
            'menu_icon' => 'dashicons-format-image',
            'enter_title_here' => 'Image Name',
            'supports' => [
                'title',
                'thumbnail',
                'custom-fields',
            ],
            'taxonomies' => [
                'image_category',
            ],
            'template' => [
                [
                    'core/paragraph',
                    [
                        'placeholder' => 'Add content...',
                    ]
                ]
            ],

            // Extended post type configuration.
            'admin_cols' => [
                'title' => [
                    'title' => 'Title',
                ],
                'thumbnail' => [
                    'title'          => 'Thumbnail',
                    'featured_image' => 'thumbnail',
                    'width'          => 48,
                    'height'         => 48,
                ],
                'image_category' => [
                    'taxonomy' => 'image_category',
                ],
                'updated' => [
                    'title'      => 'Updated',
                    'post_field' => 'post_modified',
                    'date_format' => 'Y/m/d \a\t H:i a',
                ],
            ],
        ], [
            // Override the base names used for labels (optional).
            'singular' => \__('Image', 'granola'),
            'plural'   => \__('Gallery', 'granola'),
            'slug'     => self::SLUG,
        ]);
    }

    /**
     * Adds an ACF settings page for this post type.
     */
    public static function add_settings_page(): void
    {
        if (!function_exists('acf_add_options_sub_page')) {
            return;
        }

        \acf_add_options_sub_page([
            'page_title'  => \__('Images Settings', 'granola'),
            'menu_title'  => \__('Images Settings', 'granola'),
            'menu_slug'   => 'acf-options-images-settings',
            'parent_slug' => 'edit.php?post_type=' . self::SLUG,
        ]);
    }

    /**
     * Filter the Granola Templates Post Types array to enable Template Pages for this post type.
     *
     * @see /Granola/WordPress/TemplatePage.php
     *
     * @return array The filtered post type array.
     */
    public static function filter_granola_templates_post_types($post_types)
    {
        $post_types[] = self::SLUG;
        return $post_types;
    }

    /**
     * Filter the robots meta tag to add a noindex to UI filtered pages.
     *
     * @param array $robots Associative array of robots <meta> content directives.
     * @return array The filtered array of robots <meta> content directives.
     */
    public static function filter_gallery_robots_link(array $robots): array
    {
        if (!\is_post_type_archive(self::SLUG)) {
            return $robots;
        }

        $image_category = isset($_GET['image_category']) ? sanitize_text_field($_GET['image_category']) : '';

        if (!empty($image_category)) {
            $robots['noindex'] = true;
            $robots['nofollow'] = true;
        }

        return $robots;
    }
}
