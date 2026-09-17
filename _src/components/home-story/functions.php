<?php

namespace Granola\Components\HomeStory;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'eyebrow' => null,
        'heading' => null,
        'body' => null,
        'link' => [],
        'poster' => null,
        'film_url' => null,
        'film_label' => null,
        'figures' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'home-story',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Film.
    //
    // The embed URL comes from the theme's own helper, the same one
    // gallery-video uses, so Vimeo and YouTube behave identically here and
    // there and the player parameters stay in one place. A poster with no
    // playable URL is simply a picture, which is a legitimate state.
    // -------------------------------------------------------------------------
    if (!empty($args['poster'])) {
        $args['poster']['size'] = 'large';
        $args['poster']['classes'] = ['home-story__poster'];
    }

    $args['embed_url'] = null;

    if (!empty($args['film_url'])) {
        $embed = \Theme\Utils\Videos::get_video_embed_url($args['film_url']);

        // The helper returns false for a URL it cannot parse. Treating that as
        // "no film" leaves the poster in place rather than rendering a play
        // button that loads nothing.
        $args['embed_url'] = $embed ?: null;
    }

    if (empty($args['film_label'])) {
        $args['film_label'] = \__('Play the film', 'granola');
    }

    // -------------------------------------------------------------------------
    // Figures.
    //
    // A row with no image is an empty repeater row. The stagger runs after the
    // film so the pair arrives beneath it rather than alongside.
    // -------------------------------------------------------------------------
    $args['figures'] = array_values(array_filter((array) $args['figures'], function ($figure) {
        return !empty($figure['image']);
    }));

    $args['figures'] = array_map(function ($figure, $index) {
        $figure['image']['size'] = 'large';
        $figure['image']['classes'] = ['home-story__figure-image'];
        $figure['delay'] = 160 + ($index * 140);

        return $figure;
    }, $args['figures'], array_keys($args['figures']));

    // -------------------------------------------------------------------------
    // Link.
    // -------------------------------------------------------------------------
    if (!empty($args['link'])) {
        $args['link']['classes'][] = 'home-story__link';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
