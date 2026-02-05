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
        $post_type = get_post_type();
        if ($post_type) {
            if ($post_type === 'post') {
                $posts_page = get_option('page_for_posts');
                if ($posts_page) {
                    $archive_url = get_post_field('post_name', $posts_page);
                } else {
                    $archive_url = 'blog';
                }
            } else {
                $cpt_object = get_post_type_object($post_type);
                $archive_url = $cpt_object->rewrite['slug'];
            }
        }

        $new_url = str_replace('category', "{$archive_url}/category", $term_link);

        // Remove potential duplication of archive slug in URL (e.g. /blog/blog/category/...)
        $new_url = str_replace("{$archive_url}/{$archive_url}", $archive_url, $new_url);

        return $new_url;
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
                $post_type = get_post_type();
                $archive_label = '';
                if ($post_type) {
                    if ($post_type === 'post') {
                        $posts_page = get_option('page_for_posts');
                        if ($posts_page) {
                            $archive_url = get_post_field('post_name', $posts_page);
                            $archive_label = get_the_title($posts_page);
                        } else {
                            $archive_url = 'blog';
                            $archive_label = __('Blog', 'granola');
                        }
                    } else {
                        $cpt_object = get_post_type_object($post_type);
                        $archive_url = $cpt_object->rewrite['slug'];
                        $archive_label = $cpt_object->labels->name;
                    }
                }

                $new_links[] = [
                    'url' => \home_url("/{$archive_url}/"),
                    'text' => $archive_label,
                ];

                // Update category URL
                $link['url'] = str_replace('category', "{$archive_url}/category", $link['url']);
            }

            $new_links[] = $link;
        }

        return $new_links;
    }
}
