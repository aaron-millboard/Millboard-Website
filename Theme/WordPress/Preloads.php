<?php

namespace Theme\WordPress;

class Preloads
{
    public static function init()
    {
        \add_filter('granola/wordpress/head/preload_assets', [__CLASS__, 'add_preloads']);
    }

    /**
     * Add preloads to the <head> via the 'granola/wordpress/head/preload_assets' filter.
     *
     * @see Granola/Wordpress/Head.php
     *
     * @param array $preloads An array of preload link attribute arrays.
     * @return array The filtered preload links array, with any preloads appended.
     */
    public static function add_preloads(array $preloads): array
    {
        $preloads = array_merge($preloads, [
            // Preload the display weight (F37 Ginger Light, 300) used by the hero
            // strapline and headings above the fold. The hero's "video through
            // text" is an SVG <text> cutout, which does not benefit from
            // font-display: swap, so preloading this weight lets the LCP element
            // render in the brand font immediately instead of waiting on the
            // font request. rel=preload + crossorigin are added by
            // Head::preload_theme_assets defaults.
            [
                'href' => \Granola\Asset::url('static/f37-ginger-light.woff2'),
                'as'   => 'font',
                'type' => 'font/woff2',
            ],
        ]);

        return $preloads;
    }
}
