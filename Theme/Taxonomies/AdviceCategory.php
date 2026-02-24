<?php

/**
 * Registers the 'advice_category' custom taxonomy and handles related functionality.
 */

namespace Theme\Taxonomies;

class AdviceCategory
{
    protected const SLUG = 'advice_category';

    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'register_taxonomy']);
        \add_filter('granola/templates/taxonomies', [__CLASS__, 'filter_granola_templates_taxonomies']);
        // \add_filter('advice_category_rewrite_rules', [__CLASS__, 'filter_rewrite_rules']);
        // \add_filter('term_link', [__CLASS__, 'filter_term_link'], 10, 3);
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
                'advice-centre',
            ],
            [
                // Core taxonomy configuration.
                'hierarchical'      => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite' => [
                    'slug' => 'advice-centre',
                    'with_front' => false,
                    'hierarchical' => true, // Allows hierarchical URLs if needed
                ],

                // Extended taxonomy configuration.
                'meta_box'         => 'simple',
                'exclusive'        => false, // Only one can be selected.
                'required'         => true,
                'dashboard_glance' => true,
                'allow_hierarchy' => true,
            ],
            [
                // Override the base names used for labels (optional).
                'singular' => \__('Advice Category', 'granola'),
                'plural'   => \__('Advice Categories', 'granola'),
                'slug'     => 'advice-category',
            ]
        );
    }

    /**
     * Filter the Granola Templates Taxonomies array to enable Template Pages for this taxonomy.
     *
     * @see /Granola/WordPress/TemplatePage.php
     *
     * @return array The filtered taxonomy array.
     */
    public static function filter_granola_templates_taxonomies($taxonomies): array
    {
        $taxonomies[] = self::SLUG;
        return $taxonomies;
    }

    /**
     * Filters rewrite rules used for individual permastructs.
     *
     * @param string[] $rules Array of rewrite rules generated for the current permastruct, keyed by their regex pattern.
     * @return string[] Array of rewrite rules.
     */
    public static function filter_rewrite_rules(array $rules): array
    {
        $terms = \get_terms([
            'taxonomy'   => self::SLUG,
            'hide_empty' => false,
        ]);
        $slugs = \wp_list_pluck($terms, 'slug');
        $slugs_pattern = '(' . implode('|', array_unique($slugs)) . ')';

        $new_rules = [];
        foreach ($rules as $pattern => $query) {
            $pattern = str_replace('advice-category/([^/]+)', $slugs_pattern, $pattern);
            $new_rules[$pattern] = $query;
        }
        return $new_rules;
    }

    /**
     * Remove base slug from taxonomy term link.
     *
     * @param string $link Term link URL.
     * @param \WP_Term $term Term object.
     * @param string $taxonomy Taxonomy slug.
     * @return string Term link URL.
     */
    public static function filter_term_link(string $link, \WP_Term $term, string $taxonomy): string
    {
        if ($taxonomy === self::SLUG) {
            $link = str_replace('advice-category/', '', $link);
        }

        return $link;
    }
}
