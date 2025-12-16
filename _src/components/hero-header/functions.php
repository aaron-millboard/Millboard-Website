<?php

namespace Granola\Components\HeroHeader;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'strapline' => null,
        'preheading' => null,
        'heading' => null,
        'link' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'hero-header',
        'wp-block',
    ], $args['classes']);

    if (!empty($args['heading'])) {
        $args['heading'] = [
            'content' => $args['heading'],
            'classes' => ['hero-header__heading'],
        ];
    }

    if (!empty($args['strapline'])) {
        $args['strapline'] = [
            'content' => $args['strapline'],
            'classes' => ['hero-header__strapline'],
            'el' => 'h1',
        ];

        if (!empty($args['image'])) {
            $args['attributes']['style']['--hero-header--image'] = 'url(' . $args['image']['url'] . ')';
            $args['image']['classes'] = ['hero-header__image'];
        }
    } elseif (!empty($args['heading'])) {
        // Make heading <h1> if strapline not set.
        $args['heading']['el'] = 'h1';
    }

    if (!empty($args['link'])) {
        $args['link']['classes'][] = 'g-button';
        $args['link']['classes'][] = 'hero-header__link';
    }

    if (!empty($args['ctas'])) {
        $args['ctas'] = array_map(function ($item) {
            if (!empty($item['image_desktop'])) {
                $item['image_desktop']['classes'] = [
                    'hero-header__cta-image',
                    'hero-header__cta-image--desktop',
                ];
            }

            if (!empty($item['image_mobile'])) {
                $item['image_mobile']['classes'] = [
                    'hero-header__cta-image',
                    'hero-header__cta-image--mobile',
                ];
            }

            if (!empty($item['link'])) {
                $item['link']['classes'][] = 'hero-header__cta-link';
            }

            return $item;
        }, $args['ctas']);
    }

     // Process video URL (YouTube or Vimeo).
    if (!empty($args['video_url'])) {
        $args['embed_url'] = \Theme\Utils\Videos::get_video_embed_url($args['video_url']);

        $args['media_button'] = [
            'content' => implode([
                \Granola\SVG::get('icons-custom/play.svg', [
                    'class' => 'hero-header__play-button',
                ]),
                \Granola\SVG::get('icons-custom/pause.svg', [
                    'class' => 'hero-header__pause-button',
                ]),
            ]),
            'classes' => ['hero-header__controls'],
            'attributes' => [
                'data-embed-url' => $args['embed_url'],
                'aria-label' => \__('Play video', 'granola'),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
