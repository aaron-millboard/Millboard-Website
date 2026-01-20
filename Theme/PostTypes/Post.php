<?php

/**
 * Handles core 'Post' post type related functionality.
 */

namespace Theme\PostTypes;

class Post
{
    protected const SLUG = 'post';

    public static function init(): void
    {
        \add_filter('granola/templates/post-types', [__CLASS__, 'filter_granola_templates_post_types']);
        \add_action('init', [__CLASS__, 'add_rewrite_rules'], 50, 0);
    }

    /**
     * Filter Category rewrite rules so we can view category archives for this post type only.
     *
     * @return void
     */
    public static function add_rewrite_rules()
    {
        \add_rewrite_rule('^blog/category/([^/]*)/?', 'index.php?category_name=$matches[1]&post_type=' . self::SLUG, 'top');
        \add_rewrite_rule('^blog/category/(.+?)/page/?([0-9]{1,})/?$', 'index.php?category_name=$matches[1]&paged=$matches[2]&post_type=' . self::SLUG, 'top');
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
}
