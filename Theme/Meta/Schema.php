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
            return self::add_local_business($graph);
        }

        if (\is_page()) {
            return self::add_directory_item_list($graph);
        }

        return $graph;
    }

    /**
     * Adds a LocalBusiness for the partner being viewed, and points the
     * WebPage's mainEntity at it.
     */
    private static function add_local_business(array $graph): array
    {
        $post_id = \get_the_ID();

        if (empty($post_id)) {
            return $graph;
        }

        $permalink = \get_permalink($post_id);
        $id = $permalink . '#localbusiness';

        $business = [
            '@type' => 'LocalBusiness',
            '@id' => $id,
            'name' => \get_the_title($post_id),
            'url' => $permalink,
        ];

        $address = self::build_postal_address($post_id);

        if (!empty($address)) {
            $business['address'] = $address;
        }

        $geo = self::build_geo($post_id);

        if (!empty($geo)) {
            $business['geo'] = $geo;
        }

        $phone = self::field($post_id, 'phone');

        if ($phone !== '') {
            $business['telephone'] = $phone;
        }

        $email = self::field($post_id, 'email');

        if ($email !== '') {
            $business['email'] = $email;
        }

        // The partner's own site, where they have one, is the authoritative
        // entity — sameAs rather than url, which points at this profile.
        $website = self::field($post_id, 'website');

        if ($website !== '') {
            $business['sameAs'] = $website;
        }

        $image = \get_the_post_thumbnail_url($post_id, 'large');

        if (!empty($image)) {
            $business['image'] = $image;
        }

        $description = self::partner_type_label($post_id);

        if ($description !== '') {
            $business['description'] = $description;
        }

        $graph[] = $business;

        // Link the business to the page it is described on.
        foreach ($graph as $index => $piece) {
            if (($piece['@type'] ?? '') === 'WebPage') {
                $graph[$index]['mainEntity'] = ['@id' => $id];
                break;
            }
        }

        return $graph;
    }

    /**
     * Adds an ItemList naming what a directory page lists, in the order the
     * finder renders them. Only runs on pages that actually carry a map block.
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
            'posts_per_page' => 200,
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
                'url' => \get_permalink($listing_id),
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
