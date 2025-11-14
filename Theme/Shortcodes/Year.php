<?php

namespace Theme\Shortcodes;

/**
 * A shortcode to output the current year.
 *
 * Useful for copyright notices and as a starting point for new shortcode classes.
 */
class Year
{
    public static function init()
    {
        \add_shortcode('year', [__CLASS__, 'year_shortcode']);
    }

    public static function year_shortcode()
    {
        return date('Y');
    }
}
