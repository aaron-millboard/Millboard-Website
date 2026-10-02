<?php

/**
 * Registers 'Advice Centre' CPT & handles related functionality.
 */

namespace Theme\PostTypes;

class AdviceCentre
{
    protected const SLUG = 'advice-centre';
    protected const TAXONOMY = 'advice_category';

    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'register_post_type']);
        \add_action('init', [__CLASS__, 'add_permalink_rewrite_rule']);
        \add_action('pre_get_posts', [__CLASS__, 'filter_archive_posts_per_page']);
        // Holds the company facts the advice author panel shows under every author.
        \add_action('acf/init', [__CLASS__, 'add_settings_page']);
        \add_filter('granola/templates/post-types', [__CLASS__, 'filter_granola_templates_post_types']);
        \add_filter('post_type_link', [__CLASS__, 'filter_post_type_link'], 10, 2);
        // Ahead of core's redirect_canonical (10), which would first send
        // ?paged=2 to /page/2/ and make this a second hop.
        \add_action('template_redirect', [__CLASS__, 'redirect_paged_hub'], 9);
        \add_filter('wpseo_adjacent_rel_url', [__CLASS__, 'filter_hub_adjacent_rel_url']);
        \add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_block_styles'], 20);
    }

    /**
     * Load the advice blocks' own stylesheets in the head of the pages that use
     * them.
     *
     * Their styles are separate sheets (styles/block.scss), not part of the
     * theme bundle, because Perfmatters' Remove Unused CSS on en-GB keeps ONE
     * trimmed copy of the CSS per kind of page, cut down to what the first page
     * it saw happened to contain. Every article shares one copy and every
     * taxonomy archive (the shop's categories included) shares another, so a
     * table, a quote, a reviewer line or the filter's hidden state could be
     * stripped from all of them. As their own sheets, listed in Perfmatters'
     * excluded stylesheets, they load whole.
     *
     * Granola enqueues a block's sheet when the block renders, which is after
     * the head is printed, so the link would land at the foot of the page and
     * the content could paint unstyled first. Enqueued here instead, from the
     * blocks the page is about to render.
     */
    public static function enqueue_block_styles(): void
    {
        $content = '';
        $object = \get_queried_object();

        if (\is_singular(self::SLUG) && $object instanceof \WP_Post) {
            $content = (string) $object->post_content;
        } elseif ($object instanceof \WP_Term && $object->taxonomy === self::TAXONOMY) {
            $template = \Granola\WordPress\TemplatePage::get_template_page($object);
            $content = $template instanceof \WP_Post ? (string) $template->post_content : '';
        } elseif (\is_post_type_archive(self::SLUG)) {
            $template = \Granola\WordPress\TemplatePage::get_template_page(\get_post_type_object(self::SLUG));
            $content = $template instanceof \WP_Post ? (string) $template->post_content : '';
        }

        if ($content === '' || !preg_match_all('/<!-- wp:acf\/(advice-[a-z-]+)/', $content, $matches)) {
            return;
        }

        foreach (array_unique($matches[1]) as $name) {
            // The schema block draws nothing on the page; its sheet is for the
            // editor only.
            if ($name === 'advice-schema') {
                continue;
            }

            \Granola\Component::enqueue_style_by_filename($name);
        }
    }

    /**
     * Whether this request is the hub, drawn from a template page that does not
     * paginate. See \Theme\Utils\Advice::template_is_static() for why that
     * matters.
     *
     * Not a category view. The `/advice-centre/advice-category/<term>/` rule
     * below sets this post type on a term query, which makes WordPress call it
     * the post type archive too, and its page 2 was being sent to the hub. The
     * category class deals with those, and sends them to their own category.
     */
    protected static function hub_does_not_paginate(): bool
    {
        if (!\is_post_type_archive(self::SLUG) || \is_tax() || \is_search()) {
            return false;
        }

        return \Theme\Utils\Advice::template_is_static(
            \Granola\WordPress\TemplatePage::get_template_page(\get_post_type_object(self::SLUG))
        );
    }

    /**
     * Send a page number on the hub back to the hub itself, permanently.
     */
    public static function redirect_paged_hub(): void
    {
        if (!\is_paged() || !self::hub_does_not_paginate()) {
            return;
        }

        \wp_safe_redirect(\Theme\Utils\Advice::unpaged_url((string) \get_post_type_archive_link(self::SLUG)), 301);
        exit;
    }

    /**
     * No rel="next" or rel="prev" on the hub when it does not paginate.
     *
     * They would point Google at the page URLs that now redirect back here.
     *
     * @param string $url The adjacent page URL Yoast is about to print.
     * @return string
     */
    public static function filter_hub_adjacent_rel_url($url)
    {
        return self::hub_does_not_paginate() ? '' : $url;
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
            '^advice-centre/advice-category/([^/]+)/?$',
            'index.php?post_type=' . self::SLUG . '&name=$matches[1]',
            'top'
        );

        \register_extended_post_type(self::SLUG, [
            // Core post type configuration.
            'public' => true,
            'has_archive' => self::SLUG,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => [
                'slug' => self::SLUG,
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
            // A new article starts on the article template. The body goes in
            // advice-prose; the hero, byline and author panel fill themselves
            // from the article and its author; the samples band stays hidden
            // until its links are set.
            'template' => [
                ['acf/advice-article-hero'],
                ['acf/advice-article-byline'],
                ['acf/advice-prose'],
                ['acf/advice-author-panel'],
                ['acf/advice-samples-cta'],
                ['acf/advice-schema'],
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
     * Sets the archive queries to 12 posts per page to match the template-loop card grid.
     *
     * @param \WP_Query $query The query being run.
     */
    public static function filter_archive_posts_per_page(\WP_Query $query): void
    {
        if (\is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->is_post_type_archive(self::SLUG) || $query->is_tax('advice_category')) {
            $query->set('posts_per_page', 12);
        }
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

    /**
     * Add rewrite rule for Advice Centre posts with taxonomy hierarchy in URL.
     */
    public static function add_permalink_rewrite_rule(): void
    {
        \add_rewrite_rule(
            '^' . self::SLUG . '/([^/]+)/([^/]+)/?$',
            'index.php?post_type=' . self::SLUG . '&name=$matches[2]',
            'top'
        );
    }

    /**
     * Replace permalink for Advice Centre posts to include advice category hierarchy.
     *
     * @param string $post_link The post permalink.
     * @param \WP_Post $post    The post object.
     * @return string
     */
    public static function filter_post_type_link(string $post_link, \WP_Post $post): string
    {
        if ($post->post_type !== self::SLUG) {
            return $post_link;
        }

        $term = \Theme\Utils\Taxonomies::get_primary_term($post, self::TAXONOMY);

        if (!$term instanceof \WP_Term) {
            return $post_link;
        }

        if ($term->slug === '') {
            return $post_link;
        }

        return \home_url(\user_trailingslashit(self::SLUG . '/' . $term->slug . '/' . $post->post_name));
    }
}
