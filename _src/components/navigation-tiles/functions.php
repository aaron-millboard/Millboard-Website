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
                $cta_attributes = [
                    'target' => $tile['cta']['target'] ?? '',
                    'rel'    => $tile['cta']['rel'] ?? '',
                ];

                // Give the CTA a descriptive accessible name. The visible label is
                // usually generic ("Read more"/"Weiterlesen"), so on its own it does
                // not say where the link goes; pair it with the tile title so screen
                // readers (and link audits) get "Read more: <tile title>".
                $cta_label = \wp_strip_all_tags((string) ($tile['cta']['title'] ?? ''));
                $tile_title = \wp_strip_all_tags((string) ($tile['title'] ?? ''));
                if ($tile_title !== '') {
                    $cta_attributes['aria-label'] = $cta_label !== ''
                        ? $cta_label . ': ' . $tile_title
                        : $tile_title;
                }

                $args['tiles'][$key]['cta_data'] = [
                    'title'    => $tile['cta']['title'] ?? '',
                    'url'      => $tile['cta']['url'] ?? '',
                    'attributes' => $cta_attributes,
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
