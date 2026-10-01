<?php

/**
 * Registers the 'advice_category' custom taxonomy and handles related functionality.
 */

namespace Theme\Taxonomies;

class AdviceCategory
{
    protected const SLUG = 'advice_category';
    protected const REWRITE_SLUG = 'advice-category';

    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'register_taxonomy']);
        \add_filter('granola/templates/taxonomies', [__CLASS__, 'filter_granola_templates_taxonomies']);
        // \add_filter('advice_category_rewrite_rules', [__CLASS__, 'filter_rewrite_rules']);
        // \add_filter('term_link', [__CLASS__, 'filter_term_link'], 10, 3);
        // Ahead of core's redirect_canonical (10), which would first send
        // ?paged=2 to /page/2/ and make this a second hop.
        \add_action('template_redirect', [__CLASS__, 'redirect_paged_term'], 9);
        \add_filter('wpseo_adjacent_rel_url', [__CLASS__, 'filter_term_adjacent_rel_url']);
    }

    /**
     * The category being viewed, if it is drawn from a template page that does
     * not paginate. See \Theme\Utils\Advice::template_is_static() for why that
     * matters.
     *
     * A category with no template page of its own (and no taxonomy-wide one)
     * renders the post loop with real pagination, so its /page/2/ lists the next
     * guides and is left exactly as it is.
     */
    protected static function static_term(): ?\WP_Term
    {
        if (!\is_tax(self::SLUG) || \is_search()) {
            return null;
        }

        $term = \get_queried_object();

        if (!$term instanceof \WP_Term || $term->taxonomy !== self::SLUG) {
            return null;
        }

        $template = \Granola\WordPress\TemplatePage::get_template_page($term);

        return \Theme\Utils\Advice::template_is_static($template) ? $term : null;
    }

    /**
     * Send a page number on such a category back to the category, permanently.
     *
     * Also catches the `/advice-centre/advice-category/<term>/page/N/` form the
     * post type registers, and sends it to the category's own address.
     */
    public static function redirect_paged_term(): void
    {
        if (!\is_paged()) {
            return;
        }

        $term = self::static_term();

        if (!$term) {
            return;
        }

        $link = \get_term_link($term);

        if (\is_wp_error($link)) {
            return;
        }

        \wp_safe_redirect(\Theme\Utils\Advice::unpaged_url($link), 301);
        exit;
    }

    /**
     * No rel="next" or rel="prev" on such a category.
     *
     * They would point Google at the page URLs that now redirect back to it.
     *
     * @param string $url The adjacent page URL Yoast is about to print.
     * @return string
     */
    public static function filter_term_adjacent_rel_url($url)
    {
        return self::static_term() ? '' : $url;
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
                'public'            => true,
                'publicly_queryable'=> true,
                'query_var'         => self::SLUG,
                'hierarchical'      => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite' => [
                    'slug' => self::REWRITE_SLUG,
                    'with_front' => false,
                    'hierarchical' => true,
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
                'slug'     => self::REWRITE_SLUG,
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

