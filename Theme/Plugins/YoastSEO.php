<?php

namespace Theme\Plugins;

class YoastSEO
{
    /**
     * The taxonomy whose default is noindex but which has indexable exceptions.
     */
    private const SITEMAP_TAXONOMY = 'product_cat';

    public static function init(): void
    {
        // Reposition Yoast metabox.
        \add_filter('wpseo_metabox_prio', [__CLASS__, 'priority']);

        // Enable Yoast Breadcrumbs by default.
        \add_theme_support('yoast-seo-breadcrumbs');

        // Let the indexable shop category pages into the sitemap.
        \add_filter('wpseo_sitemap_exclude_taxonomy', [__CLASS__, 'include_product_cat_sitemap'], 10, 2);
        \add_filter('wpseo_sitemap_exclude_term', [__CLASS__, 'exclude_noindex_term'], 10, 2);
    }

    /**
     * Give product_cat a sitemap.
     *
     * Yoast drops a whole taxonomy from the sitemap index when its default is
     * noindex, and a per-term "index" override does not bring it back. So the
     * built shop category pages were indexable yet appeared in no sitemap at
     * all, discoverable only by crawling internal links.
     *
     * This only opens the door. exclude_noindex_term() decides who walks
     * through it, so the forty-odd thin terms stay out.
     *
     * @param bool $excluded Whether Yoast means to exclude the taxonomy.
     * @param string $taxonomy The taxonomy name.
     * @return bool Whether to exclude it.
     */
    public static function include_product_cat_sitemap($excluded, $taxonomy): bool
    {
        if ($taxonomy === self::SITEMAP_TAXONOMY) {
            return false;
        }

        return (bool) $excluded;
    }

    /**
     * Keep every product category out of the sitemap except the built pages.
     *
     * Deliberately an allow list, not a block list. The taxonomy default is
     * noindex, so a term belongs in the sitemap only where it has been given an
     * explicit "index" override, which is how the built pages are marked.
     * Anything added later defaults to staying out.
     *
     * @param bool $exclude Whether Yoast means to exclude the term.
     * @param \WP_Term $term The term.
     * @return bool Whether to exclude it.
     */
    public static function exclude_noindex_term($exclude, $term): bool
    {
        if (!($term instanceof \WP_Term) || $term->taxonomy !== self::SITEMAP_TAXONOMY) {
            return (bool) $exclude;
        }

        $meta = \get_option('wpseo_taxonomy_meta', []);
        $setting = $meta[$term->taxonomy][$term->term_id]['wpseo_noindex'] ?? 'default';

        return $setting !== 'index';
    }

    /**
     * Reduce the priority of the Yoast meta box so it sits below content meta fields.
     *
     * @return string The new Yoast metabox priority.
     */
    public static function priority(): string
    {
        return 'low';
    }
}
