<?php

namespace Theme\Meta;

/**
 * Ownership-verification <meta> tags for third-party SEO tools.
 *
 * Used instead of a root verification file: the site redirects every root path
 * to /en-gb/, so a file at the web root (e.g. /seobilityverify_xxx.html) is
 * never served, it is 302'd to /en-gb/ and 404s. The tag goes on the front page
 * only, which is the page these tools fetch. Add tokens to the map as needed.
 */
class SiteVerification
{
    /**
     * @var array<string, string> meta name => verification token.
     */
    private const TOKENS = [
        'seobility' => '07db059dc446ed20f4837be8f2bd7c05',
    ];

    public static function init(): void
    {
        \add_filter('granola/wordpress/head/meta', [__CLASS__, 'add_meta']);
    }

    /**
     * Append the verification meta items on the front page.
     *
     * @param array $meta The head meta items.
     *
     * @return array
     */
    public static function add_meta(array $meta): array
    {
        if (!\is_front_page()) {
            return $meta;
        }

        foreach (self::TOKENS as $name => $content) {
            $meta[] = ['name' => $name, 'content' => $content];
        }

        return $meta;
    }
}
