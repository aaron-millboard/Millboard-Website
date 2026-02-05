<?php

/**
 * Handles core 'Category' taxonomy related functionality.
 */

namespace Theme\Taxonomies;

class Category
{
    protected const SLUG = 'category';

    public static function init(): void
    {
        \add_filter('granola/templates/taxonomies', [__CLASS__, 'filter_granola_templates_taxonomies']);
        \add_filter('category_link', [__CLASS__, 'filter_category_link'], 10);
        \add_filter('wpseo_breadcrumb_links', [__CLASS__, 'filter_yoast_breadcrumb_links'], 10);
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
     * Filter Category links to point to Case Study URLs, where relevant, and to Blog URLs elsewhere.
     *
     * @param string $term_link The original Category URL.
     * @return string The filtered Category URL.
     */
    public static function filter_category_link(string $term_link): string
    {
        // Case study singles and archive point category links to case study filtered URLs.
        if (\is_singular('case-study') || \is_post_type_archive('case-study')) {
            return str_replace('category', 'case-studies/category', $term_link);
        }

        // Fallback - all other versions should point to the blog.
        return str_replace('category', 'blog/category', $term_link);
    }

    /**
     * Filter Yoast breadcrumb links to insert Case Studies archive before category on case study pages,
     * or Blog archive before category elsewhere.
     *
     * @param array $links The breadcrumb links array.
     * @return array The filtered breadcrumb links array.
     */
    public static function filter_yoast_breadcrumb_links(array $links): array
    {
        $new_links = [];
        
        foreach ($links as $index => $link) {
            // Check if this is a category link
            if (isset($link['url']) && strpos($link['url'], '/category/') !== false) {
                // Case study singles and archive - insert Case Studies archive before category
                if (\is_singular('case-study') || \is_post_type_archive('case-study')) {
                    // Insert Case Studies archive breadcrumb before the category
                    $new_links[] = [
                        'url' => \home_url('/case-studies/'),
                        'text' => 'Case Studies',
                    ];
                    
                    // Update category URL
                    $link['url'] = str_replace('category', 'case-studies/category', $link['url']);
                } else {
                    // Insert Blog archive breadcrumb before the category
                    $new_links[] = [
                        'url' => \home_url('/blog/'),
                        'text' => 'Blog',
                    ];
                    
                    // Update category URL
                    $link['url'] = str_replace('category', 'blog/category', $link['url']);
                }
            }
            
            $new_links[] = $link;
        }

        return $new_links;
    }
}
