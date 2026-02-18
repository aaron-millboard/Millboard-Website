<?php

/**
 * Registers 'Installer' CPT & handles related functionality.
 */

namespace Theme\PostTypes;

class Installer
{
    protected const SLUG = 'installer';

    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'register_post_type']);
        // \add_action('acf/init', [__CLASS__, 'add_settings_page']);
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
            'show_ui' => true,
            'has_archive' => false,
            'hierarchical' => false,
            'show_in_rest' => true,
            'menu_position' => 25, // Below comments.
            'menu_icon' => 'dashicons-hammer',
            'enter_title_here' => 'Installer Name',
            'rewrite' => [
                'slug' => 'installers',
            ],
            'supports' => [
                'title',
                'editor',
                'revisions',
                'custom-fields',
            ],
            'taxonomies' => [
                'installer_type',
            ],
            'template' => [
                [
                    'acf/page-header',
                    [
                        'lock' => [
                            'remove' => true,
                            'move' => true,
                        ]
                    ]
                ],
                ['acf/partner-contact-form'],
                [
                    'core/paragraph',
                    [
                        'placeholder' => 'Add content...',
                    ]
                ]
            ],


            // Extended post type configuration.
            'admin_filters' => [],
            'admin_cols' => [
                'title' => [
                    'title' => 'Installer Name',
                ],
                'updated' => [
                    'title'      => 'Updated',
                    'post_field' => 'post_modified',
                    'date_format' => 'Y/m/d \a\t H:i a',
                ],
            ],
        ], [
            // Override the base names used for labels (optional).
            'singular' => \__('Installer', 'granola'),
            'plural'   => \__('Installers', 'granola'),
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
            'page_title'  => \__('Installers Settings', 'granola'),
            'menu_title'  => \__('Installers Settings', 'granola'),
            'menu_slug'   => 'acf-options-installers-settings',
            'parent_slug' => 'edit.php?post_type=' . self::SLUG,
        ]);
    }
}
