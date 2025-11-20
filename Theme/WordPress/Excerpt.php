<?php

namespace Theme\WordPress;

class Excerpt
{
    public static function init()
    {
        \add_filter('excerpt_more', [__CLASS__, 'filter_excerpt_more']);
        \add_filter('excerpt_length', [__CLASS__, 'filter_excerpt_length']);
        \add_action('init', [__CLASS__, 'add_excerpt_for_page']);
    }

    /**
     * Add "..." to the excerpt.
     *
     * @return string The text appended to the excerpt.
     */
    public static function filter_excerpt_more()
    {
        return '&hellip;';
    }

    /**
     * Set the excerpt length.
     *
     * @return int The new excerpt length.
     */
    public static function filter_excerpt_length()
    {
        return 20;
    }

    /**
     * Excerpts for pages.
     *
     * @return void
     */
    public static function add_excerpt_for_page()
    {
        add_post_type_support('page', 'excerpt');
    }
}
