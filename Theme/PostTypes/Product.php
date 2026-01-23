<?php

/**
 * Handles core 'Page' post type related functionality.
 */

namespace Theme\PostTypes;

class Product
{
    protected const SLUG = 'product';

    public static function init(): void
    {
        \add_filter('use_block_editor_for_post_type', [__CLASS__, 'activate_gutenberg_block_editor'], 10, 2);
        \add_filter('register_post_type_args', [__CLASS__, 'filter_register_post_type_args'], 10, 2);
        \add_action('init', [__CLASS__, 'add_rewrite_rules'], 10);
        \add_filter('query_vars', [__CLASS__, 'filter_query_vars']);
    }

    public static function filter_register_post_type_args($args, $post_type)
    {
        if ($post_type !== self::SLUG) {
            return $args;
        }

        $args['template'] = [
            ['acf/wc-single-product'],
        ];

        return $args;
    }

    public static function activate_gutenberg_block_editor($can_edit, $post_type)
    {
        if ($post_type === 'product') {
            return true;
        }

        return $can_edit;
    }

    /**
     * Filter Product rewrite rules so that attributes can be used in URLs.
     *
     * @return void
     */
    public static function add_rewrite_rules()
    {
        \add_rewrite_rule('product/([^/]+)/([^/]+)/?$', 'index.php?product=$matches[1]&attribute_pa_colour=$matches[2]', 'top');
    }

    public static function filter_query_vars($query_vars)
    {
        $query_vars[] = 'attribute_pa_colour';
        return $query_vars;
    }
}
