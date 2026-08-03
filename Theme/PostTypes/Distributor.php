<?php

/**
 * Registers 'Distributor' CPT & handles related functionality.
 */

namespace Theme\PostTypes;

class Distributor
{
    protected const SLUG = 'distributor';

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
            'menu_icon' => 'dashicons-location',
            'enter_title_here' => 'Distributor Name',
            'rewrite' => [
                'slug' => 'distributors',
            ],
            'supports' => [
                'title',
                'editor',
                'revisions',
                'custom-fields',
            ],
            'taxonomies' => [
                'distributor_type',
            ],
            // Profile layout. The page header and the contact form are existing
            // blocks and stay as they are; the distributor blocks sit between them.
            // The contact card and opening hours share a row, which is why they are
            // wrapped in columns rather than listed flat.
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
                ['acf/distributor-location-status'],
                // A full-width group, not core/columns. The theme gives any block
                // without an alignment a 20rem right padding
                // (--block--right-space), which crushed the pair into the left of
                // the page. The group's own CSS restores the content width and
                // pairs the cards with auto-fit, so whichever card renders alone
                // takes the full width instead of leaving a void.
                [
                    'core/group',
                    [
                        'align' => 'full',
                        'className' => 'distributor-cards',
                    ],
                    [
                        ['acf/distributor-contact-card'],
                        ['acf/distributor-opening-hours'],
                    ]
                ],
                ['acf/distributor-whats-on-display'],
                ['acf/distributor-location-map'],
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
                    'title' => 'Distributor Name',
                ],
                'updated' => [
                    'title'      => 'Updated',
                    'post_field' => 'post_modified',
                    'date_format' => 'Y/m/d \a\t H:i a',
                ],
            ],
        ], [
            // Override the base names used for labels (optional).
            'singular' => \__('Distributor', 'granola'),
            'plural'   => \__('Distributors', 'granola'),
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
            'page_title'  => \__('Distributors Settings', 'granola'),
            'menu_title'  => \__('Distributors Settings', 'granola'),
            'menu_slug'   => 'acf-options-distributors-settings',
            'parent_slug' => 'edit.php?post_type=' . self::SLUG,
        ]);
    }
}
