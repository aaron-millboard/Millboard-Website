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
            'top' => \_x('Top Navigation', 'Menu name', 'granola'),
            'header' => \_x('Header', 'Menu name', 'granola'),
            // The quick links on the right of the header's utility strip: find an
            // installer, find a distributor, and so on. A menu location rather
            // than option fields, so each locale sets its own -- the UK has
            // distributors and installers where the US has dealers, and the
            // strip should not have to hardcode either. Optional: with nothing
            // assigned, the strip falls back to the audience selector, the
            // language switcher and the samples call to action alone.
            'header-utility' => \_x('Header Utility Links', 'Menu name', 'granola'),
            'footer-1' => \_x('Footer 1', 'Menu name', 'granola'),
            'footer-2' => \_x('Footer 2', 'Menu name', 'granola'),
            'footer-3' => \_x('Footer 3', 'Menu name', 'granola'),
            'footer-4' => \_x('Footer 4', 'Menu name', 'granola'),
            'footer-5' => \_x('Footer 5', 'Menu name', 'granola'),
            // The legal strip along the bottom of the footer: terms, privacy,
            // cookies. A location of its own rather than a sixth column,
            // because these links are an obligation rather than navigation --
            // people look for them when they need them, and burying them in a
            // column of resources is how they get missed. Optional: with
            // nothing assigned the strip is not rendered at all.
            'footer-legal' => \_x('Footer Legal', 'Menu name', 'granola'),
            'language-switcher' => \_x('Language Switcher', 'Menu name', 'granola'),
        ]);
    }
}
