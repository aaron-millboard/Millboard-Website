<?php

namespace Granola\Components\Timeline;

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
        'background' => 'none',
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'timeline',
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
    // Background color
    // -------------------------------------------------------------------------
    if (!empty($args['background']) && $args['background'] !== 'none') {
        $args['classes'][] = 'has-' . $args['background'] . '-background-color';
        $args['classes'][] = 'has-background';
    }

    // -------------------------------------------------------------------------
    // Process timeline items
    // -------------------------------------------------------------------------
    if (!empty($args['items']) && is_array($args['items'])) {
        foreach ($args['items'] as $key => $item) {
            // Generate unique ID for each timeline item
            $args['items'][$key]['id'] = 'timeline-item-' . ($key + 1);

            // Process image if exists
            if (!empty($item['image'])) {
                $args['items'][$key]['image'] = [
                    'attachment_id' => $item['image']
                ];
            }
        }
    }

    // -------------------------------------------------------------------------
    // Add data attribute for JS
    // -------------------------------------------------------------------------
    $args['attributes']['data-timeline-count'] = count($args['items']);

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
