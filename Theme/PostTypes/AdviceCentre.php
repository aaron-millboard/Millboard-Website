<?php

/**
 * Registers 'Advice Centre' CPT & handles related functionality.
 */

namespace Theme\PostTypes;

class AdviceCentre
{
    protected const SLUG = 'advice-centre';
    public const ARCHIVE_POSTS_PER_PAGE = 12;

    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'register_post_type']);
        \add_action('pre_get_posts', [__CLASS__, 'filter_archive_posts_per_page']);
        \add_action('parse_request', [__CLASS__, 'parse_request'], 1);
        // \add_action('acf/init', [__CLASS__, 'add_settings_page']);
        \add_filter('granola/templates/post-types', [__CLASS__, 'filter_granola_templates_post_types']);
        \add_filter('post_type_link', [__CLASS__, 'filter_post_type_link'], 10, 2);
        \add_filter('request', [__CLASS__, 'filter_request']);
        \add_filter('redirect_canonical', [__CLASS__, 'filter_redirect_canonical'], 10, 2);
    }

    /**
     * Register CPT.
     *
     * @link https://github.com/johnbillion/extended-cpts/wiki/Registering-Post-Types
     */
    public static function register_post_type(): void
    {
        if (!function_exists('register_extended_post_type')) {
            return;
        }

        \add_rewrite_tag('%advice_category_slug%', '([^/]+)', 'advice_category_slug=');
        \add_rewrite_rule(
            '^advice-centre/advice-category/(.+?)/page/?([0-9]{1,})/?$',
            'index.php?advice_category=$matches[1]&paged=$matches[2]&post_type=' . self::SLUG,
            'top'
        );
        \add_rewrite_rule(
            '^advice-centre/advice-category/(.+?)/?$',
            'index.php?advice_category=$matches[1]&post_type=' . self::SLUG,
            'top'
        );

        \register_extended_post_type(self::SLUG, [
            // Core post type configuration.
            'public' => true,
            'has_archive' => self::SLUG,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => [
                'slug' => 'advice-centre/%advice_category_slug%',
                'with_front' => false,
            ],
            'menu_position' => 25, // Below comments.
            'menu_icon' => 'dashicons-format-status',
            'supports' => [
                'title',
                'editor',
                'excerpt',
                'revisions',
                'thumbnail',
                'author',
                'custom-fields',
            ],
            'taxonomies' => [
                'advice_category',
            ],
            'template' => [
                [
                    'core/paragraph',
                    [
                        'placeholder' => 'Add content...',
                    ]
                ],
            ],

            // Extended post type configuration.
            'admin_filters' => [],
            'admin_cols' => [
                'thumbnail' => [
                    'title'          => 'Thumbnail',
                    'featured_image' => 'thumbnail',
                    'width'          => 80,
                    'height'         => 80,
                ],
                'title' => [
                    'title' => 'Title',
                ],
                'author' => [
                    'title' => 'Author',
                ],
                'advice_category' => [
                    'taxonomy' => 'advice_category',
                ],
                'updated' => [
                    'title'      => 'Updated',
                    'post_field' => 'post_modified',
                    'date_format' => 'Y/m/d \a\t H:i a',
                ],
            ],
        ], [
            // Override the base names used for labels (optional).
            'singular' => \__('Advice Article', 'granola'),
            'plural'   => \__('Advice Articles', 'granola'),
            'slug'     => self::SLUG,
        ]);
    }

    /**
     * Adds an ACF settings page for this post type.
     */
    public static function add_settings_page(): void
    {
        if (!function_exists('acf_add_options_sub_page')) {
            return;
        }

        \acf_add_options_sub_page([
            'page_title'  => \__('Advice Articles Settings', 'granola'),
            'menu_title'  => \__('Advice Articles Settings', 'granola'),
            'menu_slug'   => 'acf-options-advice-articles-settings',
            'parent_slug' => 'edit.php?post_type=' . self::SLUG,
        ]);
    }

    /**
     * Filter the Granola Templates Post Types array to enable Template Pages for this post type.
     *
     * @see /Granola/WordPress/TemplatePage.php
     *
     * @return array The filtered post type array.
     */
    public static function filter_granola_templates_post_types($post_types)
    {
        $post_types[] = self::SLUG;
        return $post_types;
    }

    public static function filter_archive_posts_per_page(\WP_Query $query): void
    {
        if (\is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->is_post_type_archive(self::SLUG) || $query->is_tax('advice_category')) {
            $query->set('posts_per_page', self::ARCHIVE_POSTS_PER_PAGE);
        }
    }

    /**
     * Filters Advice Centre post permalinks to include Advice Category slug.
     *
     * @param string $post_link The post permalink.
     * @param \WP_Post $post The post object.
     * @return string The filtered permalink.
     */
    public static function filter_post_type_link(string $post_link, \WP_Post $post): string
    {
        if ($post->post_type !== self::SLUG || strpos($post_link, '%advice_category_slug%') === false) {
            return $post_link;
        }

        $terms = \wp_get_post_terms($post->ID, 'advice_category');

        if (\is_wp_error($terms) || empty($terms)) {
            return str_replace('/%advice_category_slug%', '', $post_link);
        }

        return str_replace('%advice_category_slug%', $terms[0]->slug, $post_link);
    }

    /**
     * Resolves uncategorised Advice Centre posts at /advice-centre/{post-slug}.
     *
     * @param array $query_vars The query variables from the request.
     * @return array The filtered query variables.
     */
    public static function filter_request(array $query_vars): array
    {
        if (!empty($query_vars['name'])) {
            return $query_vars;
        }

        $slug = '';

        if (!empty($query_vars['advice_category'])) {
            $slug = (string) $query_vars['advice_category'];
        } elseif (($query_vars['taxonomy'] ?? '') === 'advice_category' && !empty($query_vars['term'])) {
            $slug = (string) $query_vars['term'];
        }

        if ($slug === '' || \term_exists($slug, 'advice_category')) {
            return $query_vars;
        }

        $post = \get_page_by_path($slug, OBJECT, self::SLUG);

        if (!$post instanceof \WP_Post) {
            return $query_vars;
        }

        unset($query_vars['advice_category'], $query_vars['taxonomy'], $query_vars['term'], $query_vars['error']);
        $query_vars['post_type'] = self::SLUG;
        $query_vars['name'] = $post->post_name;

        return $query_vars;
    }

    /**
     * Parses incoming request path and resolves uncategorised Advice Centre posts.
     *
     * @param \WP $wp The WP environment instance.
     */
    public static function parse_request(\WP $wp): void
    {
        if (\is_admin() || empty($wp->request)) {
            return;
        }

        $request_path = trim((string) $wp->request, '/');
        $segments = array_values(array_filter(explode('/', $request_path)));
        $slug_index = array_search(self::SLUG, $segments, true);

        if ($slug_index === false) {
            return;
        }

        $remaining_segments = array_slice($segments, $slug_index + 1);

        if (count($remaining_segments) !== 1) {
            return;
        }

        $slug = (string) $remaining_segments[0];

        if ($slug === '' || \term_exists($slug, 'advice_category')) {
            return;
        }

        $post = \get_page_by_path($slug, OBJECT, self::SLUG);

        if (!$post instanceof \WP_Post) {
            return;
        }

        unset($wp->query_vars['advice_category'], $wp->query_vars['taxonomy'], $wp->query_vars['term'], $wp->query_vars['error']);
        $wp->query_vars['post_type'] = self::SLUG;
        $wp->query_vars['name'] = $post->post_name;
    }

    /**
     * Prevents canonical redirects for valid uncategorised Advice Centre post URLs.
     *
     * @param string|false $redirect_url The redirect URL.
     * @param string $requested_url The requested URL.
     * @return string|false The filtered redirect URL.
     */
    public static function filter_redirect_canonical($redirect_url, string $requested_url)
    {
        if (empty($redirect_url)) {
            return $redirect_url;
        }

        $path = (string) \wp_parse_url($requested_url, PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $slug_index = array_search(self::SLUG, $segments, true);

        if ($slug_index === false) {
            return $redirect_url;
        }

        $remaining_segments = array_slice($segments, $slug_index + 1);

        if (count($remaining_segments) !== 1) {
            return $redirect_url;
        }

        $slug = (string) $remaining_segments[0];

        if ($slug === '' || \term_exists($slug, 'advice_category')) {
            return $redirect_url;
        }

        $post = \get_page_by_path($slug, OBJECT, self::SLUG);

        if (!$post instanceof \WP_Post) {
            return $redirect_url;
        }

        return false;
    }
}
