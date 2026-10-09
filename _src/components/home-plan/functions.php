<?php

namespace Granola\Components\HomePlan;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'eyebrow' => null,
        'heading' => null,
        'steps' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'home-plan',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Steps.
    //
    // A row with no title is an empty repeater row. The number is generated
    // rather than typed: an editor reordering the rows should not have to
    // renumber them, and a list that reads 01, 02, 04 is worse than no numbers.
    // -------------------------------------------------------------------------
    $args['steps'] = array_values(array_filter((array) $args['steps'], function ($step) {
        return !empty($step['title']);
    }));

    $args['steps'] = array_map(function ($step, $index) {
        $step['number'] = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
        $step['delay'] = $index * 98;

        return $step;
    }, $args['steps'], array_keys($args['steps']));

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
