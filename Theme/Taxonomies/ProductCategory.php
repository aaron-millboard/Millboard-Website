<?php

/**
 * Registers a custom taxonomy and manages related functionality.
 */

namespace Theme\Taxonomies;

class ProductCategory
{
    protected const SLUG = 'product_cat';

    /**
     * Per-page count for product category term archives.
     *
     * Sized so a genuine board or cladding range renders as a single page (the
     * largest is currently 34 products) while the head and accessory categories
     * still paginate rather than shipping 150 cards in one response.
     */
    protected const ARCHIVE_POSTS_PER_PAGE = 48;

    public static function init(): void
    {
        \add_filter('granola/templates/taxonomies', [__CLASS__, 'filter_granola_templates_taxonomies']);
        \add_action('pre_get_posts', [__CLASS__, 'filter_archive_posts_per_page'], 20);
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
     * Sets the per-page count on product category term archives.
     *
     * Terms without a Template Page fall back to the template-loop component,
     * which mirrors the main query, so this governs both the rendered card grid
     * and the paginated URLs WordPress serves. Left alone these archives inherit
     * the site's `posts_per_page` of 9 and split a 19 board range over 3 pages.
     *
     * Runs after WooCommerce's own product_query so the value is not overwritten.
     *
     * @param \WP_Query $query The query being run.
     */
    public static function filter_archive_posts_per_page(\WP_Query $query): void
    {
        if (\is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->is_tax(self::SLUG)) {
            $query->set('posts_per_page', self::ARCHIVE_POSTS_PER_PAGE);
        }
    }
}
