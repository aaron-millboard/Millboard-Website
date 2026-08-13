<?php

namespace Granola\Components\Map\Listing;

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
    // Installers use their own accreditation badge when they have one, per Dan. It is
    // drawn at 31x40, so a detailed badge will read as a small coloured blob; the
    // simplified marks it replaces were made for that size. Falls back to the standard
    // pin whenever a record has no badge, so the map never loses a marker.
    if (!empty($args['id']) && ($args['post_type'] ?? '') === 'installer') {
        $badge = \Granola\Components\Map\installer_badge_url((int) $args['id']);

        if ($badge !== '') {
            $args['attributes']['data-map-item-marker-url'] = $badge;
        }
    }

    if (empty($args['attributes']['data-map-item-marker-url']) && !empty($args['marker'])) {
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
