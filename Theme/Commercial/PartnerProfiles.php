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
    private static string $name = '';

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
                self::$name = \get_the_title($found);
                self::$title = self::$name . ' | Millboard';
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
        echo self::set_breadcrumb(self::render_body($source, $type, $slug), self::hub($type));
        \get_footer();
        exit;
    }

    /**
     * The commercial page a profile sits under in its breadcrumb: the finder that lists this partner
     * type (a finder map of that one type, or a mixed one that includes it), or the Experience
     * Centres hub. Null when the commercial site has no such page.
     */
    private static function hub(string $type): ?array
    {
        global $wpdb;

        if ($type === 'experience_centre') {
            $id = (int) $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' AND post_name = 'experience-centres' LIMIT 1");
        } else {
            $id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' AND post_content LIKE %s
                 AND (post_content LIKE %s OR post_content LIKE %s)
                 ORDER BY (post_content LIKE %s) DESC, ID LIMIT 1",
                '%wp:acf/map %',
                '%"content_type":"' . $type . '"%',
                '%"sources":[%"' . $type . '"%',
                '%"content_type":"' . $type . '"%'
            ));
        }

        return $id ? ['url' => (string) \get_permalink($id), 'text' => \get_the_title($id)] : null;
    }

    /**
     * Replaces the breadcrumb in the rendered body with Home / finder / partner on the commercial
     * site. Yoast builds its trail from the page the head was printed for (here the commercial
     * site's own query, which reads "Home / Latest News"), and its filter did not reliably reach a
     * body rendered on another site, so the trail is written directly instead.
     */
    private static function set_breadcrumb(string $html, ?array $hub): string
    {
        return (string) preg_replace_callback('#<nav class="breadcrumbs">(.*?)</nav>#s', static function (array $m) use ($hub): string {
            preg_match('#<a [^>]*>(.*?)</a>#s', $m[1], $home);
            $crumbs = ['<span><a href="' . \esc_url(\home_url('/')) . '">' . ($home[1] ?? 'Home') . '</a></span>'];

            if ($hub) {
                $crumbs[] = '<span><a href="' . \esc_url($hub['url']) . '">' . \esc_html($hub['text']) . '</a></span>';
            }

            $crumbs[] = '<span class="breadcrumb_last" aria-current="page">' . \esc_html(self::$name) . '</span>';

            return '<nav class="breadcrumbs"><span class="breadcrumbs__yoast-wrapper">'
                . implode(' <span class="breadcrumbs__yoast-separator">/</span> ', $crumbs)
                . '</span></nav>';
        }, $html, 1);
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
