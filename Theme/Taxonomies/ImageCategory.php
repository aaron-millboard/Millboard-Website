<?php

/**
 * Registers the 'image_category' custom taxonomy and handles related functionality.
 */

namespace Theme\Taxonomies;

class ImageCategory
{
    protected const SLUG = 'image_category';

    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'register_taxonomy']);
    }

    /**
     * Register Taxonomy.
     *
     * @link https://github.com/johnbillion/extended-cpts/wiki/Registering-taxonomies
     */
    public static function register_taxonomy(): void
    {
        if (!function_exists('register_extended_taxonomy')) {
            return;
        }

        \register_extended_taxonomy(
            self::SLUG,
            [
                'image',
            ],
            [
                // Core taxonomy configuration.
                'hierarchical'      => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,

                // Extended taxonomy configuration.
                'meta_box'         => 'simple',
                'exclusive'        => true, // Only one can be selected.
                'required'         => true,
                'dashboard_glance' => true,
            ],
            [
                // Override the base names used for labels (optional).
                'singular' => \__('Image Category', 'granola'),
                'plural'   => \__('Image Categories', 'granola'),
                'slug'     => self::SLUG,
            ]
        );
    }
    
}
