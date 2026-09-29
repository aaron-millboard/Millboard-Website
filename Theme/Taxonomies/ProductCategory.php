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
        \add_filter('wpseo_breadcrumb_links', [__CLASS__, 'filter_breadcrumb_add_range'], 20);

        \add_action('init', [__CLASS__, 'register_indexed_sitemap']);
        \add_filter('wpseo_sitemap_index_links', [__CLASS__, 'filter_sitemap_index_links']);
    }

    /**
     * The shop category terms that are explicitly set to index.
     *
     * Yoast drops a whole taxonomy from the sitemap when its default is
     * noindex, and `noindex-tax-product_cat` is true here on purpose: the
     * taxonomy holds 40-odd thin terms (widths, touch-up-coating, fixings) that
     * must stay out of the index. Only the built category pages carry an
     * explicit per-term `wpseo_noindex = 'index'` override.
     *
     * So the list is built FROM those overrides. That makes it fail-closed: a
     * term can only appear here by having been deliberately set to index, and if
     * this code ever stops running the result is no sitemap, which is exactly
     * where the site is today, rather than 40 thin pages being exposed.
     *
     * @return array<\WP_Term> The terms, in name order.
     */
    protected static function indexed_terms(): array
    {
        $meta = \get_option('wpseo_taxonomy_meta', []);

        if (!is_array($meta) || empty($meta[self::SLUG]) || !is_array($meta[self::SLUG])) {
            return [];
        }

        $terms = [];

        foreach ($meta[self::SLUG] as $term_id => $fields) {
            if (!is_array($fields) || ($fields['wpseo_noindex'] ?? '') !== 'index') {
                continue;
            }

            $term = \get_term((int) $term_id, self::SLUG);

            if (empty($term) || \is_wp_error($term)) {
                continue;
            }

            $terms[] = $term;
        }

        \usort($terms, static fn($a, $b) => \strcasecmp($a->name, $b->name));

        return $terms;
    }

    /**
     * Register the extra sitemap with Yoast.
     */
    public static function register_indexed_sitemap(): void
    {
        global $wpseo_sitemaps;

        if (empty($wpseo_sitemaps) || !\is_object($wpseo_sitemaps)
            || !\method_exists($wpseo_sitemaps, 'register_sitemap')) {
            return;
        }

        $wpseo_sitemaps->register_sitemap('indexed-product-category', [__CLASS__, 'render_indexed_sitemap']);
    }

    /**
     * Add the extra sitemap to the sitemap index.
     *
     * @param array $links The index entries.
     * @return array The filtered entries.
     */
    public static function filter_sitemap_index_links(array $links): array
    {
        if (empty(self::indexed_terms())) {
            return $links;
        }

        $links[] = [
            'loc' => \home_url('/indexed-product-category-sitemap.xml'),
            'lastmod' => \get_lastpostmodified('gmt'),
        ];

        return $links;
    }

    /**
     * Render the extra sitemap.
     */
    public static function render_indexed_sitemap(): void
    {
        global $wpseo_sitemaps;

        if (empty($wpseo_sitemaps) || !\is_object($wpseo_sitemaps)) {
            return;
        }

        $xml = '';

        foreach (self::indexed_terms() as $term) {
            $url = \get_term_link($term);

            if (\is_wp_error($url)) {
                continue;
            }

            $xml .= "\t<url>\n"
                . "\t\t<loc>" . \esc_url($url) . "</loc>\n"
                . "\t\t<changefreq>weekly</changefreq>\n"
                . "\t\t<priority>0.8</priority>\n"
                . "\t</url>\n";
        }

        if ($xml === '') {
            return;
        }

        $wpseo_sitemaps->set_sitemap(
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n" . $xml . '</urlset>'
        );
    }

    /**
     * Put the range back into a product breadcrumb when the trail stops at the
     * top level.
     *
     * Yoast builds a product's trail from its Yoast PRIMARY category, and on
     * this site 85 of 151 decking products carry the top-level Composite
     * Decking as their primary rather than their range. Those read
     *
     *     Home / Shop / Composite Decking / Antique Oak - 176mm
     *
     * while a product whose primary IS its range reads the fuller
     *
     *     Home / Shop / Composite Decking / Enhanced Grain / Antique Oak - 126mm
     *
     * The primary category is deliberately NOT changed to fix this. The product
     * URL is built from the primary, so changing it would move 85 live product
     * URLs for a cosmetic gain. The crumb is added here instead, from terms the
     * product already carries, and nothing moves.
     *
     * Only fires when the deepest category crumb is a TOP-LEVEL term. If the
     * trail already goes deeper it is left alone, which is what stops a width
     * term such as "126mm" being appended to a trail that already ends at
     * Enhanced Grain.
     *
     * Skips when more than one direct child matches, rather than guessing.
     *
     * @param array $links The breadcrumb links Yoast built.
     * @return array The filtered links.
     */
    public static function filter_breadcrumb_add_range(array $links): array
    {
        if (!\is_singular('product') || empty($links)) {
            return $links;
        }

        $product = \get_queried_object();

        if (!($product instanceof \WP_Post)) {
            return $links;
        }

        // The deepest product_cat crumb already in the trail, and where it sits.
        $deepest = null;
        $at = -1;

        foreach ($links as $index => $link) {
            if (!is_array($link)) {
                continue;
            }

            // Current Yoast hands this filter crumbs shaped
            // {url, text, term_id, taxonomy}. The older WPSEO_Breadcrumbs shape
            // carried a WP_Term under 'term'. Both are accepted, because
            // checking only for the object made this filter a no-op.
            $term = null;

            if (isset($link['term']) && $link['term'] instanceof \WP_Term) {
                $term = $link['term'];
            } elseif (!empty($link['term_id']) && ($link['taxonomy'] ?? '') === self::SLUG) {
                $found = \get_term((int) $link['term_id'], self::SLUG);

                if (!empty($found) && !\is_wp_error($found)) {
                    $term = $found;
                }
            }

            if ($term instanceof \WP_Term && $term->taxonomy === self::SLUG) {
                $deepest = $term;
                $at = $index;
            }
        }

        // Nothing to extend, or the trail already goes below the top level.
        if ($deepest === null || (int) $deepest->parent !== 0) {
            return $links;
        }

        $terms = \get_the_terms($product->ID, self::SLUG);

        if (empty($terms) || \is_wp_error($terms)) {
            return $links;
        }

        $children = [];

        foreach ($terms as $term) {
            if ((int) $term->parent === (int) $deepest->term_id) {
                $children[] = $term;
            }
        }

        // Ambiguous, so leave it rather than pick one.
        if (count($children) !== 1) {
            return $links;
        }

        $range = $children[0];
        $url = \get_term_link($range);

        if (\is_wp_error($url)) {
            return $links;
        }

        // Emitted in the same shape as the crumb it follows.
        \array_splice($links, $at + 1, 0, [[
            'url' => $url,
            'text' => $range->name,
            'term_id' => (int) $range->term_id,
            'taxonomy' => self::SLUG,
        ]]);

        return $links;
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
