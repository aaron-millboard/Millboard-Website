<?php

/**
 * Handles core 'Page' post type related functionality.
 */

namespace Theme\PostTypes;

class Page
{
    protected const SLUG = 'page';

    public static function init(): void
    {
        \add_filter('register_post_type_args', [__CLASS__, 'filter_register_post_type_args'], 10, 2);
    }

    public static function filter_register_post_type_args($args, $post_type)
    {
        if ($post_type !== self::SLUG) {
            return $args;
        }

        $args['template'] = [
            ['acf/page-header', [
                'lock' => [
                    'remove' => true,
                    'move' => true,
                ]
            ]],
            [
                'core/paragraph',
                [
                    'placeholder' => 'Add content...',
                ]
            ]
        ];

        return $args;
    }
}
