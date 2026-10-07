<?php

namespace Granola\Components\AdviceSamplesCta;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'image' => null,
        'eyebrow' => null,
        'heading' => null,
        'heading_accent' => null,
        'body' => null,
        'primary_link' => [],
        'secondary_link' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'advice-samples-cta',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Image. Decorative: it sets the scene for the copy over it.
    // -------------------------------------------------------------------------
    $args['image'] = !empty($args['image']['attachment_id']) ? [
        'attachment_id' => (int) $args['image']['attachment_id'],
        'alt' => '',
        'size' => 'full',
        'sizes' => '100vw',
        'classes' => ['advice-samples-cta__image'],
    ] : null;

    \Theme\Utils\Advice::mark_image_shown((int) ($args['image']['attachment_id'] ?? 0));

    // -------------------------------------------------------------------------
    // Links. An empty link field is an array with no URL.
    // -------------------------------------------------------------------------
    foreach (['primary_link', 'secondary_link'] as $key) {
        if (empty($args[$key]['url']) || empty($args[$key]['title'])) {
            $args[$key] = null;
        }
    }

    // A samples band with no way to order samples says nothing, so until its
    // link is set (a new article starts with the band empty) it is not drawn.
    // The editor still shows it, so it can be filled in.
    if (!$args['primary_link'] && empty($args['is_preview'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
