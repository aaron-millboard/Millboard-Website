<?php

namespace Granola\Components\LogoGrid;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'columns' => '',
        'layout' => 'grid',
        'items' => [],
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'logo-grid',
        'wp-block',
        'animate',
    ], $args['classes']);

    // Add type
    $args['classes'][] = 'logo-grid--' . $args['layout'];

    // Generate items array.
    if (!empty($args['logos'])) {
        foreach ($args['logos'] as $logo) {
            if (!empty($logo['image'])) {
                $image = array_merge($logo['image'], [
                    'size' => 'medium',
                ]);

                $args['items'][] = [
                    'image' => $image,
                    'link' => $logo['link'] ?? null,
                ];
            }
        }
        // Tidy up $args by removing logos.
        unset($args['logos']);
    }

    if (!empty($args['columns'])) {
        $args['classes'][] = 'cards--columns-' . $args['columns'];
    }

    if (!empty($args['background_color']) && $args['background_color'] !== 'none') {
        $args['classes'][] = 'has-' . $args['background_color'] . '-background-color';
        $args['classes'][] = 'has-background';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
