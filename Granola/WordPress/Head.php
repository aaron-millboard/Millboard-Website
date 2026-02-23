<?php

namespace Granola\WordPress;

/**
 * Handles any non-cleanup <head> functionality.
 *
 * @see Cleanup.php
 */
class Head
{
    public static function init(): void
    {
        \add_action('wp_head', [__CLASS__, 'meta_elements'], 0);
        \add_action('wp_head', [__CLASS__, 'link_elements'], 0);
        \add_action('wp_head', [__CLASS__, 'javascript_detection'], 0);

        \add_filter('granola/wordpress/head/meta', [__CLASS__, 'add_theme_color_meta']);
        \add_filter('granola/wordpress/head/links', [__CLASS__, 'add_webmanifest_link']);
        \add_filter('granola/wordpress/head/links', [__CLASS__, 'preload_theme_assets']);
    }

    /**
     * Output <meta> elements in the <head>.
     *
     * Outputs meta elements from a filtered array of attribute arrays. Includes several defaults.
     */
    public static function meta_elements(): void
    {
        $meta_items = \apply_filters('granola/wordpress/head/meta', [
            [
                'charset' => \get_bloginfo('charset')
            ],
            [
                'name' => 'viewport',
                'content' => 'width=device-width, initial-scale=1, viewport-fit=cover',
            ],
        ]);


        foreach ($meta_items as $meta_item) {
            echo "<meta " . \Granola\Helpers::build_attributes($meta_item) . ">\n";
        }
    }

    /**
     * Add theme color <meta> tag to the head.
     *
     * Hooks into the `granola/wordpress/head/meta` filter to add the site manifest theme color value.
     *
     * @param array $meta An array of meta attribute arrays.
     * @return array The filtered meta array, with theme color data appended.
     */
    public static function add_theme_color_meta(array $meta): array
    {
        $manifest = \Granola\Asset::decoded_content('static/site.webmanifest');

        if (!empty($manifest['theme_color'])) {
            $meta[] = [
                'name' => 'theme-color',
                'content' => $manifest['theme_color'],
            ];
        }

        return $meta;
    }

    /**
     * Output <link> elements in the <head>.
     *
     * Outputs link elements from a filtered array of attribute arrays. Includes the theme web manifest by default.
     */
    public static function link_elements(): void
    {
        $links = \apply_filters('granola/wordpress/head/links', []);

        foreach ($links as $link) {
            if (!empty($link['href'])) {
                echo "<link " . \Granola\Helpers::build_attributes($link) . ">\n";
            }
        }
    }


    /**
     * Add webmanifest link to the head for PWA support.
     *
     * Hooks into the `granola/wordpress/head/links` filter to add webmanifest.
     *
     * @see _src/static/site.webmanifest
     *
     * @param array $links An array of link attribute arrays.
     * @return array The links array with the webmanifest added
     */
    public static function add_webmanifest_link(array $links): array
    {
        if (!apply_filters('granola/config/enable_webmanifest', false)) {
            return $links;
        }

        $links[] = [
            'rel' => 'manifest',
            'href' => \Granola\Asset::url('static/site.webmanifest'),
            'crossorigin' => 'use-credentials',
        ];

        return $links;
    }


    /**
     * Add preload <link> tags to the head.
     *
     * Hooks into the `granola/wordpress/head/links` filter to add preload assets.
     * The 'rel' attribute for these assets can still be overriden with another value, e.g. 'prefetch'.
     *
     * @see /config.php
     *
     * @param array $links An array of link attribute arrays.
     * @return array The filtered links array, with any preloads appended.
     */
    public static function preload_theme_assets(array $links): array
    {
        $preload_assets = \apply_filters('granola/wordpress/head/preload_assets', []);
        if (empty($preload_assets) || !is_array($preload_assets)) {
            return $links;
        }

        $defaults = [
            'rel'        => 'preload',
            'href'        => '',
            'crossorigin' => 'anonymous',
        ];

        foreach ($preload_assets as $asset) {
            $links[] = array_merge($defaults, $asset);
        }

        return $links;
    }

    /**
     * Output JavaScript detection script.
     *
     * Adds a `js` class to the root `<html>` element when JavaScript is detected.
     * Needs to be added in the header to avoid FOUC.
     */
    public static function javascript_detection(): void
    {
        echo "<script>(function(html){html.className = " .
        "html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>\n";
    }
}
