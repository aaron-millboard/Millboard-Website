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

    if (!empty($args['post']) && $args['post'] instanceof \WP_Post) {
        $terms = \Theme\Meta\ObjectMeta::get_object_labels($args['post'], [
            'limit' => 1,
            'taxonomies' => [
                'installer_type',
            ],
        ]);

        if (!empty($terms[0])) {
            $args['tag'] = [
                'content' => $terms[0]['name'],
                'classes' => [
                    'g-tag',
                ],
            ];
        }
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
