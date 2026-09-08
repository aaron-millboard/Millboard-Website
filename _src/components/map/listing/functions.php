<?php

namespace Granola\Components\Map\Listing;

/**
 * The "Stock available" line is OFF until there is a real per-branch stock list.
 *
 * Same reason as the profile badge, written up in full in
 * _src/components/distributor-location-status/functions.php: `holds_stock` is true
 * on all 190 UK distributor records because a distributor CAN order any product,
 * which is not the same as holding it.
 *
 * This one is enforced by clearing the arg rather than by guarding the template,
 * because index.php is included in the GLOBAL namespace and would not see this
 * constant. Clearing it here means index.php's existing `!empty($args['holds_stock'])`
 * check does the work, and Map.js stops finding .map__listing__stock to copy into
 * the marker tooltip, so the card and the tooltip cannot disagree.
 *
 * TO RE-ENABLE: set this to true AND SHOW_STOCK_STATE in the profile component.
 */
const SHOW_STOCK_STATE = false;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'link' => [],
        'tag' => [],
        'email' => '',
        'phone' => '',
        'marker' => '',
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'map__listing',
    ], $args['classes']);

    if (empty($args['address'])) {
        return null;
    }

    if (!SHOW_STOCK_STATE) {
        $args['holds_stock'] = false;
    }

    $lat = $args['address']['lat'] ?? '';
    $lng = $args['address']['lng'] ?? '';

    $args['attributes']['data-map-item-lat'] = $lat;
    $args['attributes']['data-map-item-lng'] = $lng;

    if (!empty($args['advanced_installer'])) {
        $args['attributes']['data-map-item-advanced-installer'] = '1';
    }

    // Resolve the map pin here so PHP owns the path (and its cache-busting
    // version) rather than the script rebuilding it from a hardcoded theme URL.
    //
    // The installer pins ARE the accreditation badges, drawn as pins by design rather
    // than the record's own badge image scaled down. That was the interim approach and
    // it squashed a 197x300 logo into a 31px pin; these are drawn for the size.
    if (!empty($args['marker'])) {
        $args['attributes']['data-map-item-marker-url'] = \Granola\Components\Map\marker_icon_url($args['marker']);
    }

    // Finally set address (the google_map field returns an array).
    $args['address'] = $args['address']['address'] ?? '';

    // Directions link to the listing's coordinates (card action).
    $args['directions_url'] = ($lat !== '' && $lng !== '')
        ? 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($lat . ',' . $lng)
        : '';

    // Detail-page link (the "More info" card action). Kept in $args['link'] too
    // for the marker popup, which reads .map__listing__link for the detail href.
    if (!empty($args['url'])) {
        $args['link'] = [
            'content' => \_x('More info', 'Map listing detail link', 'granola'),
            'url' => $args['url'],
            'classes' => [
                'map__listing__link',
            ],
        ];
    }

    // Location-type tag (Stockist / Showspace / Experience Centre / installer
    // type). Prefer the pre-resolved type_label from get_item_data; fall back to
    // the installer_type term for listings not built via it (e.g. custom items).
    // The g-tag inside .map__listing__meta is also read by the marker popup badge.
    $type_label = trim((string) ($args['type_label'] ?? ''));

    if ($type_label === '' && !empty($args['post']) && $args['post'] instanceof \WP_Post) {
        $terms = \Theme\Meta\ObjectMeta::get_object_labels($args['post'], [
            'limit' => 1,
            'taxonomies' => [
                'installer_type',
            ],
        ]);

        if (!empty($terms[0])) {
            $type_label = $terms[0]['name'];
        }
    }

    if ($type_label !== '') {
        $args['tag'] = [
            'content' => $type_label,
            'classes' => [
                'g-tag',
                'map__listing__type',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
