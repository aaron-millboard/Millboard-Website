<?php

namespace Granola\Components\HomeCaseStudy;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'eyebrow' => null,
        'heading' => null,
        'meta' => null,
        'image' => null,
        'link' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'home-case-study',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Image.
    //
    // Full bleed and up to 88svh tall, so it asks for a larger source than the
    // tiles do. 'large' tops out at 1024 and would be upscaled on any desktop.
    // -------------------------------------------------------------------------
    if (!empty($args['image'])) {
        $args['image']['size'] = 'full';
        $args['image']['classes'] = ['home-case-study__image'];

        // Deliberately over 100vw below 1100px.
        //
        // This is a cover crop into a box that is taller than the
        // photograph's own aspect, so the source has to be WIDER than the
        // viewport for its height to reach. Left to the default 100vw, the
        // CDN sizes to the viewport width and the browser then scales the
        // result up to fill the height: 1.21x on a tablet, which is visible
        // softness on the largest photograph on the page.
        //
        // 760px of box against a 4:3 source needs about 1010px of width, so
        // the hint is roughly that, expressed against each band's width.
        $args['image']['sizes'] = '(max-width: 700px) 260vw, (max-width: 1100px) 130vw, 100vw';
    }

    // -------------------------------------------------------------------------
    // Button.
    //
    // Spring Wood rather than the handoff's dark primary: this button sits on
    // a photograph under a scrim that is 82% Charcoal Black at the bottom, and
    // a Charcoal Black button with a Charcoal Black border disappears into it.
    // -------------------------------------------------------------------------
    if (!empty($args['link'])) {
        $args['link']['classes'][] = 'g-button';
        $args['link']['classes'][] = 'home-case-study__link';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
