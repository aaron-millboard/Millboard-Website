<?php

namespace Theme\Meta;

/**
 * Structured data for the partner directories and partner profiles.
 *
 * Both additions extend Yoast's existing @graph rather than printing a second
 * JSON-LD block, so there is one graph per page and the pieces can reference
 * each other by @id.
 *
 * - Partner profiles (installer / distributor / Experience Centre / showroom)
 *   describe the business itself with LocalBusiness, built from the Partner
 *   Details fields. The page is genuinely about that business, so it is the
 *   WebPage's mainEntity.
 * - Directory pages (any page carrying the acf/map block) summarise what they
 *   list with an ItemList of ListItems. Deliberately name + url only: the
 *   businesses are described in full on their own profiles, so repeating them
 *   here would duplicate the same entities dozens of times.
 */
class Schema
{
    /**
     * Post types that represent a partner business.
     */
    private const PARTNER_POST_TYPES = [
        'installer',
        'distributor',
        'experience_centre',
        'showroom',
    ];

    public static function init(): void
    {
        \add_filter('wpseo_schema_graph', [self::class, 'filter_graph'], 11, 2);
    }

    /**
     * @param array $graph   The Yoast schema graph.
     * @param mixed $context Yoast's meta tags context.
     *
     * @return array
     */
    public static function filter_graph($graph, $context = null): array
    {
        if (!is_array($graph)) {
            return $graph;
        }

        if (\is_singular(self::PARTNER_POST_TYPES)) {
            $post_id = (int) \get_the_ID();

            return $post_id ? self::add_local_business($graph, $post_id, $post_id) : $graph;
        }

        if (\is_page()) {
            $page_id = (int) \get_the_ID();
            $record_id = $page_id ? self::partner_record_for_page($page_id) : 0;

            if ($record_id) {
                $graph = self::add_local_business($graph, $record_id, $page_id);
            }

            return self::add_directory_item_list($graph);
        }

        return $graph;
    }

    /**
     * Adds a LocalBusiness for the partner being viewed, and points the
     * WebPage's mainEntity at it.
     *
     * The record holding the Partner Details and the page a customer lands on
     * are the same post for a distributor, and two different posts for an
     * Experience Centre (see partner_record_for_page()). So every field is read
     * from $record_id, while every URL comes from $page_id.
     */
    private static function add_local_business(array $graph, int $record_id, int $page_id): array
    {
        $permalink = (string) \get_permalink($page_id);

        if ($permalink === '') {
            return $graph;
        }

        $id = $permalink . '#localbusiness';

        $business = [
            '@type' => 'LocalBusiness',
            '@id' => $id,
            'name' => \get_the_title($record_id),
            'url' => $permalink,
        ];

        $address = self::build_postal_address($record_id);

        if (!empty($address)) {
            $business['address'] = $address;
        }

        $geo = self::build_geo($record_id);

        if (!empty($geo)) {
            $business['geo'] = $geo;
        }

        $phone = self::field($record_id, 'phone');

        if ($phone !== '') {
            $business['telephone'] = $phone;
        }

        $email = self::field($record_id, 'email');

        if ($email !== '') {
            $business['email'] = $email;
        }

        $hours = self::build_opening_hours($record_id);

        if (!empty($hours)) {
            $business['openingHoursSpecification'] = $hours;
        }

        // The partner's own site, where they have one, is the authoritative
        // entity — sameAs rather than url, which points at this profile.
        $website = self::field($record_id, 'website');

        if ($website !== '') {
            $business['sameAs'] = $website;
        }

        // The record carries the branch photography. An Experience Centre keeps
        // its imagery on its page instead, so fall back to that rather than
        // describe a business with no image at all.
        $image = \get_the_post_thumbnail_url($record_id, 'large')
            ?: \get_the_post_thumbnail_url($page_id, 'large');

        if (!empty($image)) {
            $business['image'] = $image;
        }

        $description = self::partner_type_label($record_id);

        if ($description !== '') {
            $business['description'] = $description;
        }

        $graph[] = $business;

        // Link the business to the page it is described on. @type can be a
        // string or an array (Yoast uses ["WebPage","FAQPage"] where a page has
        // an FAQ), and on those pages Yoast has already pointed mainEntity at
        // the questions — so never overwrite an existing value.
        foreach ($graph as $index => $piece) {
            $types = (array) ($piece['@type'] ?? []);

            if (!in_array('WebPage', $types, true)) {
                continue;
            }

            if (empty($piece['mainEntity'])) {
                $graph[$index]['mainEntity'] = ['@id' => $id];
            }

            break;
        }

        return $graph;
    }

    /**
     * Adds an ItemList naming what a directory page lists, alphabetically. Only
     * runs on pages that actually carry a map block. The finder itself orders by
     * distance from the visitor, which is decided per request and so cannot be
     * described in markup the page cache serves to everyone.
     */
    private static function add_directory_item_list(array $graph): array
    {
        $post_id = \get_the_ID();

        if (empty($post_id) || !\has_block('acf/map', $post_id)) {
            return $graph;
        }

        $post_types = self::map_block_post_types($post_id);

        if (empty($post_types)) {
            return $graph;
        }

        $listings = \get_posts([
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => 500,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
            'no_found_rows' => true,
            'suppress_filters' => false,
        ]);

        if (empty($listings)) {
            return $graph;
        }

        $elements = [];
        $position = 1;

        foreach ($listings as $listing_id) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => \get_the_title($listing_id),
                'url' => self::partner_public_url($listing_id),
            ];

            $position++;
        }

        $permalink = \get_permalink($post_id);

        $graph[] = [
            '@type' => 'ItemList',
            '@id' => $permalink . '#itemlist',
            'name' => \get_the_title($post_id),
            'numberOfItems' => count($elements),
            'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
            'itemListElement' => $elements,
        ];

        return $graph;
    }

    /**
     * Reads the post types a page's map block is configured to list, so the
     * ItemList matches what the finder actually shows.
     */
    private static function map_block_post_types(int $post_id): array
    {
        $post = \get_post($post_id);

        if (empty($post) || empty($post->post_content)) {
            return [];
        }

        $post_types = [];

        foreach (\parse_blocks($post->post_content) as $block) {
            if (($block['blockName'] ?? '') !== 'acf/map') {
                continue;
            }

            $data = $block['attrs']['data'] ?? [];
            $sources = $data['sources'] ?? ($data['content_type'] ?? []);

            if (is_string($sources)) {
                $sources = ($sources === 'multiple' || $sources === 'custom') ? [] : [$sources];
            }

            if (is_array($sources)) {
                $post_types = array_merge($post_types, $sources);
            }
        }

        // Only keep real, registered post types — stale block data has pointed at
        // post types that no longer exist.
        $post_types = array_filter(
            array_unique(array_map('sanitize_key', $post_types)),
            'post_type_exists'
        );

        return array_values($post_types);
    }

    /**
     * Builds a PostalAddress from the Partner Details address field, which is an
     * ACF Google Map field and so already carries the parts separately.
     */
    private static function build_postal_address(int $post_id): array
    {
        $address = \get_field('address', $post_id);

        if (empty($address) || !is_array($address)) {
            return [];
        }

        $street = trim(
            trim((string) ($address['street_number'] ?? '')) . ' '
            . trim((string) ($address['street_name'] ?? ''))
        );

        $parts = [
            'streetAddress' => $street,
            'addressLocality' => trim((string) ($address['city'] ?? '')),
            'addressRegion' => trim((string) ($address['state'] ?? '')),
            'postalCode' => trim((string) ($address['post_code'] ?? '')),
            'addressCountry' => trim((string) ($address['country_short'] ?? '')),
        ];

        $parts = array_filter($parts, static fn ($value) => $value !== '');

        if (empty($parts)) {
            return [];
        }

        return array_merge(['@type' => 'PostalAddress'], $parts);
    }

    /**
     * Coordinates come from the same map field, and the finder already relies on
     * them, so they are known good.
     */
    private static function build_geo(int $post_id): array
    {
        $address = \get_field('address', $post_id);

        if (empty($address) || !is_array($address)) {
            return [];
        }

        $lat = $address['lat'] ?? '';
        $lng = $address['lng'] ?? '';

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return [];
        }

        return [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
        ];
    }

    /**
     * Opening hours as OpeningHoursSpecification, built from the record's own
     * `opening_hours` repeater so nothing needs re-entering.
     *
     * Days sharing a range are grouped into one specification, which is both
     * the convention and much smaller than seven separate pieces. A day flagged
     * closed is published as 00:00 to 00:00, which is how Google documents
     * "closed all day"; leaving it out instead would say only that the hours
     * are unknown.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function build_opening_hours(int $post_id): array
    {
        $rows = \get_field('opening_hours', $post_id);

        if (empty($rows) || !is_array($rows)) {
            return [];
        }

        // Monday first, whatever order the repeater was filled in, so a grouped
        // specification reads as a week.
        $days = [
            'Monday' => 'https://schema.org/Monday',
            'Tuesday' => 'https://schema.org/Tuesday',
            'Wednesday' => 'https://schema.org/Wednesday',
            'Thursday' => 'https://schema.org/Thursday',
            'Friday' => 'https://schema.org/Friday',
            'Saturday' => 'https://schema.org/Saturday',
            'Sunday' => 'https://schema.org/Sunday',
        ];

        $by_day = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $day = trim((string) ($row['day'] ?? ''));

            if (isset($days[$day])) {
                $by_day[$day] = $row;
            }
        }

        $ranges = [];

        foreach ($days as $day => $day_uri) {
            if (!isset($by_day[$day])) {
                continue;
            }

            $row = $by_day[$day];

            if (!empty($row['closed'])) {
                $opens = '00:00';
                $closes = '00:00';
            } else {
                $opens = self::iso_time($row['open'] ?? '');
                $closes = self::iso_time($row['close'] ?? '');

                // open and close are free-text fields, so a value that is not a
                // real time, or a range of no length, is dropped rather than
                // published as structured data a search engine cannot read.
                if ($opens === null || $closes === null || $opens === $closes) {
                    continue;
                }
            }

            $ranges[$opens . '|' . $closes][] = $day_uri;
        }

        $specification = [];

        foreach ($ranges as $range => $day_uris) {
            [$opens, $closes] = explode('|', $range);

            $specification[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $day_uris,
                'opens' => $opens,
                'closes' => $closes,
            ];
        }

        return $specification;
    }

    /**
     * Normalise a hand-typed time to the 24-hour HH:MM that schema.org expects.
     *
     * Accepts what an editor plausibly types (7, 7:30, 7.30, 0730, 5.30pm) and
     * returns null for anything else, including 24:00, so an unreadable value
     * costs one day rather than invalidating the whole specification.
     */
    private static function iso_time($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || !preg_match('~^(\d{1,2})(?:[:.h]?(\d{2}))?\s*([ap]m?)?$~i', $value, $matches)) {
            return null;
        }

        $hours = (int) $matches[1];
        $minutes = (int) ($matches[2] ?? 0);
        $meridiem = strtolower(substr($matches[3] ?? '', 0, 1));

        if ($meridiem === 'p' && $hours < 12) {
            $hours += 12;
        }

        if ($meridiem === 'a' && $hours === 12) {
            $hours = 0;
        }

        if ($hours > 23 || $minutes > 59) {
            return null;
        }

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Finds the partner record an ordinary page is the public face of.
     *
     * An Experience Centre is described by an `experience_centre` record, which
     * feeds the finder, but it is published as a hand-built page. The record
     * 301s to the URL in its `directory_link`, so its own singular is never
     * served and the business schema has to be attached to that page instead.
     *
     * Only Experience Centres carry `directory_link` and there are very few of
     * them, so this reads the records and compares in PHP rather than querying
     * postmeta by value, which no index covers.
     */
    private static function partner_record_for_page(int $page_id): int
    {
        if (!\post_type_exists('experience_centre')) {
            return 0;
        }

        $path = self::url_path((string) \get_permalink($page_id));

        if ($path === '') {
            return 0;
        }

        $records = \get_posts([
            'post_type' => 'experience_centre',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => 'ID',
            'order' => 'ASC',
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        if (empty($records)) {
            return 0;
        }

        // One query for every record's meta, rather than one per get_field().
        \update_meta_cache('post', $records);

        foreach ($records as $record_id) {
            $link = \get_field('directory_link', $record_id);

            if (!is_string($link) || trim($link) === '') {
                continue;
            }

            if (self::url_path($link) === $path) {
                return (int) $record_id;
            }
        }

        return 0;
    }

    /**
     * The URL a partner is actually published at.
     *
     * An Experience Centre record redirects to its `directory_link`, so listing
     * its permalink would put a redirect in the structured data. The stored
     * value is rebuilt onto this site's own host rather than published as
     * saved, because a clone between environments has left staging URLs in this
     * field on production before.
     */
    private static function partner_public_url(int $post_id): string
    {
        $link = \get_field('directory_link', $post_id);
        $path = is_string($link) ? self::url_path($link) : '';

        if ($path === '') {
            return (string) \get_permalink($post_id);
        }

        $site = (array) \wp_parse_url(\home_url('/'));

        if (empty($site['scheme']) || empty($site['host'])) {
            return (string) \get_permalink($post_id);
        }

        return \trailingslashit($site['scheme'] . '://' . $site['host'] . $path);
    }

    /**
     * A URL reduced to the part that identifies the page: its path, without a
     * trailing slash. Used to compare and rebuild `directory_link`, whose host
     * cannot be trusted to be this environment's.
     */
    private static function url_path(string $url): string
    {
        $path = \wp_parse_url(trim($url), PHP_URL_PATH);

        return is_string($path) ? \untrailingslashit($path) : '';
    }

    /**
     * Human-readable partner type, e.g. "Millboard Advanced Installer".
     */
    private static function partner_type_label(int $post_id): string
    {
        switch (\get_post_type($post_id)) {
            case 'installer':
                return !empty(\get_field('advanced_installer', $post_id))
                    ? \__('Millboard Advanced Installer', 'granola')
                    : \__('Approved Millboard Installer', 'granola');

            case 'distributor':
                return \__('Millboard Distributor', 'granola');

            case 'experience_centre':
                return \__('Millboard Experience Centre', 'granola');

            case 'showroom':
                return \__('Millboard Showspace', 'granola');
        }

        return '';
    }

    /**
     * Trimmed string value for a Partner Details field.
     */
    private static function field(int $post_id, string $name): string
    {
        $value = \get_field($name, $post_id);

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
