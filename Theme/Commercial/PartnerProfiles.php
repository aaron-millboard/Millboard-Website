<?php

/**
 * Serves partner profiles under a /commercial/ subsite.
 *
 * A /commercial/ subsite holds no partner records of its own (its finder reads the residential
 * site, see get_directory_source_blog() in the map component). Linking finder listings to the
 * residential profile would drop a specifier into the homeowner header, footer and tone. So this
 * renders the residential record INSIDE the commercial site chrome at
 * /commercial/distributors/<slug>/, /commercial/installers/<slug>/ and so on. No data is copied,
 * and the canonical points at the residential URL, so search engines see one profile.
 */

namespace Theme\Commercial;

class PartnerProfiles
{
    // URL segment => record type. Every partner type the finder lists, or its listings would 404.
    private const TYPES = [
        'distributors' => 'distributor',
        'installers' => 'installer',
        'experience-centre' => 'experience_centre',
        'showrooms' => 'showroom',
    ];

    private static string $canonical = '';
    private static string $title = '';

    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'add_rules']);
        \add_filter('query_vars', static fn(array $vars): array => [...$vars, 'mb_partner', 'mb_partner_slug']);
        \add_action('template_redirect', [__CLASS__, 'render'], 1);
        \add_filter('wpseo_canonical', static fn($url) => self::$canonical ?: $url);
        \add_filter('wpseo_opengraph_url', static fn($url) => self::$canonical ?: $url);
        \add_filter('wpseo_title', static fn($title) => self::$title ?: $title);
    }

    /**
     * Same rule as the map component's get_directory_source_blog(): /en-gb/commercial/ reads
     * /en-gb/. Returns 0 on any other site. Kept in step with that function and its filter.
     */
    private static function source_blog(): int
    {
        $home = rtrim((string) \wp_parse_url(\home_url(), PHP_URL_PATH), '/');
        $source = 0;

        if (str_ends_with($home, '/commercial')) {
            $source = (int) \get_blog_id_from_url(
                (string) \wp_parse_url(\home_url(), PHP_URL_HOST),
                substr($home, 0, -strlen('/commercial')) . '/'
            );
        }

        $source = (int) \apply_filters('granola/map/directory_source_blog', $source);

        return $source !== \get_current_blog_id() ? $source : 0;
    }

    public static function add_rules(): void
    {
        if (!self::source_blog()) {
            return;
        }

        \add_rewrite_rule(
            '^(' . implode('|', array_keys(self::TYPES)) . ')/([^/]+)/?$',
            'index.php?mb_partner=$matches[1]&mb_partner_slug=$matches[2]',
            'top'
        );
    }

    public static function render(): void
    {
        $type = self::TYPES[(string) \get_query_var('mb_partner')] ?? '';
        $slug = (string) \get_query_var('mb_partner_slug');
        $source = self::source_blog();

        if (!$type || $slug === '' || !$source) {
            return;
        }

        // Find the record first, so the head (title, canonical) and a 404 are decided before any
        // output. Nothing is rendered yet.
        \switch_to_blog($source);

        try {
            $found = (new \WP_Query(['post_type' => $type, 'name' => $slug, 'posts_per_page' => 1, 'fields' => 'ids']))->posts[0] ?? 0;

            if ($found) {
                self::$canonical = (string) \get_permalink($found);
                self::$title = \get_the_title($found) . ' | Millboard';
            }
        } finally {
            \restore_current_blog();
        }

        if (!$found) {
            // The rewrite rule matched, so WordPress would otherwise serve this as a 200.
            $GLOBALS['wp_query']->set_404();
            \status_header(404);
            \nocache_headers();

            return;
        }

        $GLOBALS['wp_query']->is_404 = false;
        \status_header(200);
        \get_header();
        // Rendered AFTER the head, as on any other page. Block stylesheets are enqueued while a
        // block renders, so rendering first would print them BEFORE main.css and lose to its
        // defaults (the media-object gap, for one).
        echo self::render_body($source, $type, $slug);
        \get_footer();
        exit;
    }

    /**
     * The record's body, rendered on the source site so its blocks resolve their own fields,
     * images and links. The header and footer stay the commercial ones.
     */
    private static function render_body(int $source, string $type, string $slug): string
    {
        global $wp_query;
        $saved_query = $wp_query;
        $saved_post = $GLOBALS['post'] ?? null;

        \switch_to_blog($source);

        try {
            $wp_query = new \WP_Query(['post_type' => $type, 'name' => $slug, 'posts_per_page' => 1]);
            $content = [];

            while (\have_posts()) {
                \the_post();
                $content[] = \apply_filters('the_content', \get_the_content());
            }

            return \Granola\Component::get('site-main', [
                'object' => \Granola\WordPress\PageObject::get(),
                'content' => implode($content),
            ]);
        } finally {
            $wp_query = $saved_query;
            $GLOBALS['post'] = $saved_post;
            \restore_current_blog();
        }
    }
}
