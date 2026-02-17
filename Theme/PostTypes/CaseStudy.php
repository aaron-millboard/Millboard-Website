<?php

/**
 * Registers 'Case Study' CPT & handles related functionality.
 */

namespace Theme\PostTypes;

class CaseStudy
{
    protected const SLUG = 'case-study';

    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'register_post_type']);
        \add_action('acf/init', [__CLASS__, 'register_acf_fields']);
        // \add_action('acf/init', [__CLASS__, 'add_settings_page']);
        \add_filter('granola/templates/post-types', [__CLASS__, 'filter_granola_templates_post_types']);
        \add_action('init', [__CLASS__, 'add_rewrite_rules'], 10, 0);
    }

    /**
     * Filter Category rewrite rules so we can view category archives for this post type only.
     *
     * @return void
     */
    public static function add_rewrite_rules()
    {
        \add_rewrite_rule('^case-studies/category/([^/]*)/?', 'index.php?category_name=$matches[1]&post_type=' . self::SLUG, 'top');
        \add_rewrite_rule('^case-studies/category/(.+?)/page/?([0-9]{1,})/?$', 'index.php?category_name=$matches[1]&paged=$matches[2]&post_type=' . self::SLUG, 'top');
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
            'menu_position' => 25, // Below comments.
            'menu_icon' => 'dashicons-portfolio',
            'enter_title_here' => 'Case Study Name',
            'rewrite' => [
                'slug' => 'case-studies',
            ],
            'supports' => [
                'title',
                'editor',
                'excerpt',
                'revisions',
                'thumbnail',
                'author',
                'custom-fields',
            ],
            'taxonomies' => [
                'category',
            ],
            'template' => [
                ['acf/case-study-details'],
                [
                    'core/paragraph',
                    [
                        'placeholder' => 'Add content...',
                    ]
                ],
            ],

            // Extended post type configuration.
            'admin_filters' => [
                'location' => [
                    'taxonomy' => 'category',
                ],
            ],
            'admin_cols' => [
                'thumbnail' => [
                    'title'          => 'Thumbnail',
                    'featured_image' => 'thumbnail',
                    'width'          => 80,
                    'height'         => 80,
                ],
                'title' => [
                    'title' => 'Title',
                ],
                'author' => [
                    'title' => 'Author',
                ],
                'updated' => [
                    'title'      => 'Updated',
                    'post_field' => 'post_modified',
                    'date_format' => 'Y/m/d \a\t H:i a',
                ],
            ],
        ], [
            // Override the base names used for labels (optional).
            'singular' => \__('Case Study', 'granola'),
            'plural'   => \__('Case Studies', 'granola'),
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
            'page_title'  => \__('Case Studies Settings', 'granola'),
            'menu_title'  => \__('Case Studies Settings', 'granola'),
            'menu_slug'   => 'acf-options-case-studies-settings',
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
     * Register ACF fields for Case Study.
     *
     * @return void
     */
    public static function register_acf_fields(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        \acf_add_local_field_group([
            'key' => 'group_case_study_featured',
            'title' => 'Case Study',
            'fields' => [
                [
                    'key' => 'field_is_featured',
                    'name' => 'is_featured',
                    'label' => 'Featured post',
                    'type' => 'true_false',
                    'ui' => true,
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => self::SLUG,
                    ],
                ],
            ],
        ]);
    }
}
