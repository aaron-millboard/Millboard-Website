<?php

namespace Granola\Components\PostSummaries\Item;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'heading' => [],
        'image' => null,
        'content' => '',
        'object' => null,
        'tags' => [],
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['object']) || (!$args['object'] instanceof \WP_Post)) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'post-summary',
        'post-summaries__item',
    ], $args['classes']);

    /** @var object $object */
    $object = $args['object'];

    $args['heading'] = [
        'content' => \get_the_title($object->ID),
        'classes' => ['post-summary__heading'],
        'link' => \get_the_permalink($object->ID),
    ];

    $args['content'] = \get_the_excerpt($object->ID);

    if ($object instanceof \WP_Post) {
        $pt_object = \get_post_type_object($object->post_type);

        $args['tags'][] = [
            'content' => $pt_object->labels->singular_name ?? '',
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
