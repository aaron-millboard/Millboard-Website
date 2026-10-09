<?php

namespace Granola\Components\HomeFeature;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'eyebrow' => null,
        'heading' => null,
        'body' => null,
        'image' => null,
        'image_side' => 'left',
        'background' => 'springwood',
        'button_style' => 'primary',
        'link' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    //
    // The pair alternates ground as it alternates side: decking sits on Spring
    // Wood with its image left, cladding on Mist with its image right. Both are
    // editorial rather than derived from each other, because the sequence gains
    // a third section as often as not.
    // -------------------------------------------------------------------------
    $args['image_side'] = $args['image_side'] === 'right' ? 'right' : 'left';
    $args['background'] = $args['background'] === 'mist' ? 'mist' : 'springwood';

    $args['classes'] = array_merge([
        'home-feature',
        'home-feature--image-' . $args['image_side'],
        'home-feature--' . $args['background'],
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Image.
    //
    // Square at every width. The photographs are a mix of 3:2 and 4:3, and a
    // cover crop to 1:1 is what makes the two sections answer each other.
    // -------------------------------------------------------------------------
    if (!empty($args['image'])) {
        $args['image']['size'] = 'large';
        $args['image']['classes'] = ['home-feature__image'];

        // Sized for the crop, not the column.
        //
        // A square frame cut from a landscape photograph has to reach on
        // height, so the source must be wider than the box: for a 3:2
        // original that is one and a half times the frame. Left to the
        // default the browser asked for the column width and scaled the
        // result up 1.16x on a phone.
        //
        // The middle band is the widest ask of the three. Between 720 and
        // 1100 these boxes are at their tallest against their own width, so
        // a hint that suited phone and desktop still left the tablet scaling
        // up, by as much as 1.44x.
        $args['image']['sizes'] = '(max-width: 720px) 150vw, (max-width: 1099px) 110vw, min(64vw, 920px)';
    }

    // -------------------------------------------------------------------------
    // Copy slides in from the side its image is not on, so the two halves meet
    // in the middle rather than both arriving from the same direction.
    // -------------------------------------------------------------------------
    $args['body_reveal'] = $args['image_side'] === 'left'
        ? 'mbh-reveal--from-right'
        : 'mbh-reveal--from-left';

    // -------------------------------------------------------------------------
    // Button.
    // -------------------------------------------------------------------------
    if (!empty($args['link'])) {
        $args['link']['classes'][] = 'g-button';
        $args['link']['classes'][] = 'home-feature__link';
        $args['link']['classes'][] = 'home-feature__link--' . ($args['button_style'] === 'secondary' ? 'secondary' : 'primary');
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
