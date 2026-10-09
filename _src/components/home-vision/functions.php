<?php

namespace Granola\Components\HomeVision;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'eyebrow' => null,
        'heading' => null,
        'link' => [],
        'tiles' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'home-vision',
        'wp-block',
    ], $args['classes']);

    if (!empty($args['link'])) {
        $args['link']['classes'][] = 'g-button';
        $args['link']['classes'][] = 'home-vision__link';
    }

    // -------------------------------------------------------------------------
    // Tiles.
    //
    // A tile with neither an image nor a label is an empty repeater row, which
    // is what an editor leaves behind when they add one and change their mind.
    // Rendering it would put a stray chevron in the grid.
    //
    // The stagger is per tile from the second onwards, so a row arrives in
    // sequence rather than all at once. Held here rather than in CSS because
    // the count is editorial: nth-child would need a rule per possible tile.
    // -------------------------------------------------------------------------
    $args['tiles'] = array_values(array_filter((array) $args['tiles'], function ($tile) {
        return !empty($tile['image']) || !empty($tile['label']);
    }));

    $args['tiles'] = array_map(function ($tile, $index) {
        if (!empty($tile['image'])) {
            // Square at every width, so the pair reads as a pair rather than as
            // whatever aspect the photographs happened to be.
            $tile['image']['size'] = 'large';
            $tile['image']['classes'] = ['home-vision__image'];

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
            //
            // These tiles are not simply bigger on bigger screens. Just above the
            // two-column threshold the tile column is too narrow for two tiles, so
            // a single tile fills all 460px of it and needs a larger source than it
            // does at 1440, where two sit side by side at 288 each. The middle
            // band runs to 1200 to cover that.
            $tile['image']['sizes'] = '(max-width: 720px) 150vw, (max-width: 1200px) 64vw, 500px';
        }

        // The tiles follow the heading rather than arriving with it.
        //
        // The design starts the first tile at 0, level with the copy beside it.
        // On the page that reads as the heading and the decking tile landing
        // together and the cladding tile trailing, which is not a sequence, it
        // is a pair and a straggler. Starting a step later gives the block the
        // order it is read in: the words, then decking, then cladding.
        //
        // The heading itself takes no delay here -- HomeReveal fills in 0 for
        // the first element of a block that does not state one.
        $tile['delay'] = ($index + 1) * 168;

        return $tile;
    }, $args['tiles'], array_keys($args['tiles']));

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
