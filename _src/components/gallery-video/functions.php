<?php

namespace Granola\Components\GalleryVideo;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [],
        'preheading' => '',
        'heading' => '',
        'items' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'gallery-video',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Bail early if no content
    // -------------------------------------------------------------------------
    if (empty($args['items'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Process video items
    // -------------------------------------------------------------------------
    if (!empty($args['items']) && is_array($args['items'])) {
        foreach ($args['items'] as $key => $item) {
            // Process cover image
            if (!empty($item['cover_image'])) {
                $args['items'][$key]['cover_image_data'] = [
                    'attachment_id' => $item['cover_image'],
                    'size' => 'large'
                ];

                // Thumbnail for sidebar
                $args['items'][$key]['thumbnail_data'] = [
                    'attachment_id' => $item['cover_image'],
                    'size' => 'thumbnail'
                ];
            }

            // Process video URL (YouTube or Vimeo)
            if (!empty($item['video_url'])) {
                $embed_url = \Theme\Utils\Videos::get_video_embed_url($item['video_url']);
                if ($embed_url) {
                    $args['items'][$key]['embed_url'] = $embed_url;
                } else {
                    // Invalid URL, remove this item
                    unset($args['items'][$key]);
                }
            }
        }

        // Re-index array after potential unsets
        $args['items'] = array_values($args['items']);
    }

    // Add data attribute for JavaScript
    $args['attributes']['data-video-count'] = count($args['items']);

    // Add classes based on item count
    if (count($args['items']) > 1) {
        $args['classes'][] = 'gallery-video--multiple';
    } else {
        $args['classes'][] = 'gallery-video--single';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
