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
            // [
            //     'href' => \Granola\Asset::URL('static/WebFont-Regular.woff2'),
            //     'fetchpriority' => 'low',
            //     'type' => 'font/woff2',
            //     'as' => 'font',
            // ],
        ]);

        return $preloads;
    }
}
