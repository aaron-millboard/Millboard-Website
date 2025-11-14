<?php

namespace Theme\WordPress;

class Menus
{
    public static function init()
    {
        \add_filter('after_setup_theme', [__CLASS__, 'register_theme_menus']);
    }

    /**
     * Register theme menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    public static function register_theme_menus(): void
    {
        \register_nav_menus([
            'header' => \_x('Header', 'Menu name', 'granola'),
            'footer-1' => \_x('Footer 1', 'Menu name', 'granola'),
            'footer-2' => \_x('Footer 2', 'Menu name', 'granola'),
        ]);
    }
}
