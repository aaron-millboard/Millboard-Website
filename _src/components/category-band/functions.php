<?php

namespace Granola\Components\CategoryBand;

/**
 * A dark closing band with an image behind it, a heading, a line of copy and
 * two calls to action.
 *
 * The samples band at the foot of a category page. Not call-to-action: that
 * block is shared and has no dark treatment or background image.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'intro' => '',
        'image_id' => 0,
        'buttons' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'category-band',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    $args['buttons'] = normalise_buttons($args['buttons']);

    // -------------------------------------------------------------------------
    // Bail early if there is nothing to say.
    // -------------------------------------------------------------------------
    if (empty($args['heading'])) {
        return null;
    }

    return $args;
}

/**
 * The first button is filled, the rest are outlined, both on the dark ground.
 *
 * @param array $buttons The button rows.
 * @return array The buttons.
 */
function normalise_buttons(array $buttons): array
{
    $normalised = [];

    foreach ($buttons as $index => $button) {
        $link = $button['link'] ?? $button;

        if (empty($link['url']) || empty($link['title'])) {
            continue;
        }

        $normalised[] = [
            'url' => $link['url'],
            'title' => $link['title'],
            'target' => $link['target'] ?? '',
            'classes' => [
                'category-band__button',
                $index === 0 ? 'category-band__button--filled' : 'category-band__button--outline',
            ],
        ];
    }

    return $normalised;
}
