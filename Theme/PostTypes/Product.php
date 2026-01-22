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
    }

    public static function activate_gutenberg_block_editor($can_edit, $post_type)
    {
        if ($post_type === 'product') {
            return true;
        }

        return $can_edit;
    }
}
