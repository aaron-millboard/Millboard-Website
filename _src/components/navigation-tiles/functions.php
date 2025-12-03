<?php

namespace Granola\Components\NavigationTiles;

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
        'tiles' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'navigation-tiles',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Bail early if no content
    // -------------------------------------------------------------------------
    if (empty($args['tiles'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Process tiles CTA
    // -------------------------------------------------------------------------
    if (!empty($args['tiles']) && is_array($args['tiles'])) {
        foreach ($args['tiles'] as $key => $tile) {
            if (!empty($tile['cta'])) {
                $args['tiles'][$key]['cta_data'] = [
                    'title'    => $tile['cta']['title'] ?? '',
                    'url'      => $tile['cta']['url'] ?? '',
                    'attributes' => [
                        'target' => $tile['cta']['target'] ?? '',
                        'rel'    => $tile['cta']['rel'] ?? '',
                    ],
                    'classes' => [
                        'navigation-tiles__tile-cta',
                        'g-button',
                    ],
                ];
            }
        }
    }

    // -------------------------------------------------------------------------
    // Manipulate classes based on args
    // -------------------------------------------------------------------------
    if (!empty($args['background']) && $args['background'] !== 'none') {
        $args['classes'][] = 'has-' . $args['background'] . '-background-color';
        $args['classes'][] = 'has-background';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
