<?php

/**
 * Registers the Distributor Type custom taxonomy and handles related functionality.
 */

namespace Theme\Taxonomies;

class DistributorType
{
    protected const SLUG = 'distributor_type';

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
                'distributor',
            ],
            [
                // Core taxonomy configuration.
                'hierarchical'      => false,
                'show_admin_column' => true,
                'show_in_rest'      => true,

                // Extended taxonomy configuration.
                'meta_box'         => 'simple',
                'exclusive'        => true, // Only one can be selected.
                'required'         => false,
                'dashboard_glance' => true,
            ],
            [
                // Override the base names used for labels (optional).
                'singular' => \__('Distributor Type', 'granola'),
                'plural'   => \__('Distributor Types', 'granola'),
                'slug'     => self::SLUG,
            ]
        );
    }
}
