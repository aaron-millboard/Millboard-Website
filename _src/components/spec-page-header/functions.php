<?php

namespace Granola\Components\SpecPageHeader;

/**
 * Specifier page header.
 *
 * Draws the page-header block's split layout (text panel beside an image plate
 * that bleeds off the right) and adds a row of up to three calls to action:
 * primary, secondary and text. It borrows the page-header classes rather than
 * restyling anything, so it looks exactly like that block, and the page-header
 * block itself is left alone because hundreds of pages use it.
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
        'description' => '',
        'image' => 0,
        'cta_primary' => [],
        'cta_secondary' => [],
        'cta_text' => [],
        'show_breadcrumbs' => true,
    ], $args);

    if (empty($args['heading'])) {
        if (empty($args['is_preview'])) {
            return null;
        }

        $args['heading'] = \_x('Add title', 'Placeholder for page header title', 'granola');
    }

    // -------------------------------------------------------------------------
    // Required classes: the page-header split layout.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'page-header',
        'spec-page-header',
        'wp-block',
        'alignfull',
        'page-header--type--page',
        'page-header--layout--split',
    ], $args['classes']);

    $args['classes'][] = !empty($args['image']) ? 'page-header--has-media' : 'page-header--no-media';

    if (!empty($args['show_breadcrumbs'])) {
        $args['classes'][] = 'has-breadcrumbs';
    }

    // Brand 2 (Spring Wood) unless the editor picks another background. The picked colour's
    // class is added from background_color, so the default is only added when nothing is picked.
    if (empty($args['background_color']) || $args['background_color'] === 'none') {
        $args['classes'][] = 'has-brand-2-background-color';
        $args['classes'][] = 'has-background';
    }

    // -------------------------------------------------------------------------
    // Image: the same eager, high priority image the page-header block uses.
    // -------------------------------------------------------------------------
    if (!empty($args['image'])) {
        $args['image'] = [
            'attachment_id' => $args['image'],
            // The hero photograph is informative, so it keeps the alt text saved in the media library.
            'alt' => (string) \get_post_meta((int) $args['image'], '_wp_attachment_image_alt', true),
            'loading' => 'eager',
            'size' => 'full',
            'sizes' => '(min-width: 62em) 45vw, (min-width: 48em) 40vw, 100vw',
            'attributes' => [
                'fetchpriority' => 'high',
                'data-spai-eager' => true,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Heading and description.
    // -------------------------------------------------------------------------
    $args['heading'] = [
        'content' => $args['heading'],
        'el' => 'h1',
        'classes' => [
            'page-header__heading',
            'is-style-typestyle-h1',
        ],
    ];

    if (!empty($args['description'])) {
        $args['description'] = [
            'content' => \wpautop($args['description']),
            'classes' => ['page-header__description-text'],
        ];
    }

    // -------------------------------------------------------------------------
    // The calls to action, in slot order. Slot decides the look, not the page.
    // -------------------------------------------------------------------------
    $slots = [
        'cta_primary' => ['g-button--solid'],
        'cta_secondary' => [],
        'cta_text' => ['g-button--secondary'],
    ];

    $args['ctas'] = [];

    foreach ($slots as $field => $modifiers) {
        $cta = $args[$field] ?? [];

        if (empty($cta['url']) || empty($cta['title'])) {
            continue;
        }

        $args['ctas'][] = [
            'title' => $cta['title'],
            'url' => $cta['url'],
            'attributes' => [
                'target' => $cta['target'] ?? '',
                'rel' => $cta['rel'] ?? '',
            ],
            'classes' => array_merge(['page-header__cta', 'g-button'], $modifiers),
        ];
    }

    return $args;
}
