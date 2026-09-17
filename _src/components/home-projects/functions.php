<?php

namespace Granola\Components\HomeProjects;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'eyebrow' => null,
        'heading' => null,
        'link' => [],
        'hint' => null,
        'projects' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'home-projects',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Projects.
    //
    // A row with neither an image nor a title is an empty repeater row. The
    // cards are portrait crops of landscape photographs, so they ask for a
    // source taller than the rendered card: 'large' would be upscaled.
    // -------------------------------------------------------------------------
    $args['projects'] = array_values(array_filter((array) $args['projects'], function ($project) {
        return !empty($project['image']) || !empty($project['title']);
    }));

    $args['projects'] = array_map(function ($project) {
        if (!empty($project['image'])) {
            $project['image']['size'] = 'full';
            $project['image']['classes'] = ['home-projects__image'];

            // A 3:4 portrait crop of a landscape source needs a MUCH wider
            // source than the card: the height is what has to reach, so the
            // width needed is the box height times the source's own aspect.
            // A 400px card is 533px tall, and these photographs run 4:3 to 3:2,
            // so the worst case is 533 x 1.5 = 800px. Sized to the card width
            // instead, the browser scaled them up 1.32x.
            $project['image']['sizes'] = '(max-width: 500px) 160vw, 800px';
        }

        return $project;
    }, $args['projects']);

    // -------------------------------------------------------------------------
    // Link.
    // -------------------------------------------------------------------------
    if (!empty($args['link'])) {
        $args['link']['classes'][] = 'home-projects__all';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
