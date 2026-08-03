<?php

namespace Granola\Components\DistributorLocationMap;

/**
 * "Find us" map for a distributor / showroom / experience centre.
 *
 * Uses the keyless maps.google.com embed rather than the Google Maps Embed API.
 * The theme does hold a Maps key (Map\get_server_google_api_key), but the Embed
 * API is a separate product that has to be enabled on the key, and the key is
 * currently provisioned for Maps JS and Geocoding. The keyless form needs nothing
 * enabling and renders the same single pin.
 *
 * Returns null without coordinates or an address, so records the geocoder never
 * resolved hide the section instead of embedding a map of nowhere.
 */
function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'zoom' => 15,
    ], $args);

    $args['classes'] = array_merge([
        'distributor-location-map',
        'wp-block',
    ], $args['classes']);

    $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : \get_the_ID();
    if (!$post_id) {
        return empty($args['is_preview']) ? null : $args;
    }

    $address = \get_field('address', $post_id);

    $lat = is_array($address) && !empty($address['lat']) ? (float) $address['lat'] : null;
    $lng = is_array($address) && !empty($address['lng']) ? (float) $address['lng'] : null;
    $raw = is_array($address) ? trim((string) ($address['address'] ?? '')) : '';

    if (($lat === null || $lng === null) && $raw === '' && empty($args['is_preview'])) {
        return null;
    }

    if (empty($args['heading'])) {
        $args['heading'] = \__('Find us', 'granola');
    }

    $args['name'] = \get_the_title($post_id);

    // The status block already owns the short address, so reuse its formatter
    // rather than keeping two versions of the same tidy-up.
    $args['address'] = \Granola\Components\DistributorLocationStatus\short_address($address);

    // -------------------------------------------------------------------------
    // Embed and directions both prefer coordinates, which are exact, and fall
    // back to the address string.
    // -------------------------------------------------------------------------
    $query = ($lat !== null && $lng !== null) ? $lat . ',' . $lng : $raw;

    $args['embed_url'] = \add_query_arg([
        'q' => $query,
        'z' => (int) $args['zoom'],
        'output' => 'embed',
    ], 'https://maps.google.com/maps');

    $args['directions_url'] = \add_query_arg([
        'api' => 1,
        'destination' => $query,
    ], 'https://www.google.com/maps/dir/');

    /* translators: %s: the distributor name. */
    $args['embed_title'] = sprintf(\__('Map showing the location of %s', 'granola'), $args['name']);

    $args['website'] = trim((string) \get_field('website', $post_id));

    return $args;
}
