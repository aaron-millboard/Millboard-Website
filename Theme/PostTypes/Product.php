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
        // if ($post_type === 'product') {
        //     return true;
        // }

        return $can_edit;
    }
}
