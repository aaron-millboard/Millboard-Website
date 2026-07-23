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

    $args['attributes']['data-map-item-lat'] = $args['address']['lat'];
    $args['attributes']['data-map-item-lng'] = $args['address']['lng'];

    if (!empty($args['advanced_installer'])) {
        $args['attributes']['data-map-item-advanced-installer'] = '1';
    }

    // Finally set address.
    $args['address'] = $args['address']['address'];

    if (!empty($args['phone'])) {
        $args['phone'] = [
            'content' => $args['phone'],
            'url' => 'tel:' . $args['phone'],
            'classes' => [
                'map__listing__phone',
            ],
        ];
    }

    if (!empty($args['url'])) {
        $args['link'] = [
            'content' => !empty($args['post']->post_type) ? sprintf(
                \__('Contact %s', 'granola'),
                $args['post']->post_type,
            ) : \_x('Contact', 'Map listing link text', 'granola'),
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
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
