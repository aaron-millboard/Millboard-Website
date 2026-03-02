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
        'control_button' => [],
        'embed_url' => '',
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
            $args['image']['size'] = 'hero';
            $args['image']['classes'] = ['hero-header__image'];
            $args['image']['attributes']['fetchpriority'] = 'high';
            $args['image']['attributes']['data-spai-eager'] = true;
            $args['image']['loading'] = 'eager';
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
                $args['image_desktop']['size'] = 'medium_large';
            }

            if (!empty($item['image_mobile'])) {
                $item['image_mobile']['classes'] = [
                    'hero-header__cta-image',
                    'hero-header__cta-image--mobile',
                ];
                $args['image_mobile']['size'] = '1536x1536';
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

        if (!empty($args['embed_url'])) {
            if (strpos($args['embed_url'], 'youtube.com/embed/') !== false) {
                $args['embed_url'] = add_query_arg([
                    'mute' => 1,
                ], $args['embed_url']);
            } elseif (strpos($args['embed_url'], 'player.vimeo.com/video/') !== false) {
                $args['embed_url'] = add_query_arg([
                    'muted' => 1,
                    'loop' => 1,
                    'vimeo_logo' => 0,
                    'color' => '799513', // Branded: olive green.
                ], $args['embed_url']);
            }
        }

        $args['control_button'] = [
            'content' => \__('Play video', 'granola'),
            'classes' => ['hero-header__controls'],
            'attributes' => [
                'data-play-label' => \__('Play video', 'granola'),
                'data-pause-label' => \__('Pause video', 'granola'),
            ],
            'visually_hidden_text' => true,
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
