<?php

/**
 * Registers a custom taxonomy and manages related functionality.
 */

namespace Theme\Taxonomies;

class ProductCategory
{
    protected const SLUG = 'product_cat';

    public static function init(): void
    {
        \add_filter('granola/templates/taxonomies', [__CLASS__, 'filter_granola_templates_taxonomies']);
        \add_filter('wpseo_breadcrumb_links', [__CLASS__, 'filter_breadcrumb_add_range'], 20);
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
            $term = $link['term'] ?? null;

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

        \array_splice($links, $at + 1, 0, [[
            'url' => $url,
            'text' => $range->name,
            'term' => $range,
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
}
