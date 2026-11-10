<?php

namespace Granola\Components\SpecFeatures;

/**
 * Checklist of features: an icon beside a title and a line of text, with an
 * optional link under each.
 *
 * Drawn with the list-features block's own classes, so it looks the same; what
 * list-features cannot do is the link. list-features is used on live pages and
 * is left alone.
 */
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
        'features' => [],
    ], $args);

    $args['features'] = array_values(array_filter((array) $args['features'], static function ($feature) {
        return !empty($feature['title']);
    }));

    if (empty($args['features'])) {
        return null;
    }

    foreach ($args['features'] as $key => $feature) {
        if (!empty($feature['icon'])) {
            $args['features'][$key]['icon_data'] = [
                'attachment_id' => $feature['icon'],
                'size' => 'full',
            ];
        }

        if (empty($feature['link']['url'])) {
            $args['features'][$key]['link'] = null;
        }
    }

    // -------------------------------------------------------------------------
    // Required classes: list-features' own, which bring its styles.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'list-features',
        'spec-features',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // Brand 2 unless the editor picks a colour: the picked colour's class is added from background_color,
    // so a default added as well would fight it (the later rule in the stylesheet would win, not the editor's choice).
    if (empty($args['background_color']) || $args['background_color'] === 'none') {
        $args['classes'][] = 'has-brand-2-background-color';
        $args['classes'][] = 'has-background';
    }

    return $args;
}
