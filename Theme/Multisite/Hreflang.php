<?php

namespace Theme\Multisite;

/**
 * Handles the generation of hreflang links.
 */
class Hreflang
{
    public static function init()
    {
        \add_filter('granola\wordpress\head\links', [__CLASS__, 'add_hreflang_links']);
    }

    public static function add_hreflang_links(array $links): array
    {
        $page_object = \Granola\WordPress\PageObject::get();

        if (!($page_object instanceof \WP_Post)) {
            return $links;
        }

        $hreflang_links = \get_field('hreflang_links', $page_object);

        if (empty($hreflang_links)) {
            return $links;
        }

        foreach ($hreflang_links as $hreflang_link) {
            $hreflang = $hreflang_link['language'];
            if (!empty($hreflang_link['region'])) {
                $hreflang .= '-' . $hreflang_link['region'];
            }

            $links[] = [
                'rel' => 'alternate',
                'hreflang' => $hreflang,
                'href' => $hreflang_link['url'],
            ];
        }

        return $links;
    }
}
