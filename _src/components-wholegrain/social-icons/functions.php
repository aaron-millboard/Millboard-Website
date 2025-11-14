<?php

namespace Granola\Components\SocialIcons;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'title' => '',
        'networks' => \get_field('social_networks', 'option'),
        'show' => true,
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if ($args['show'] === false || empty($args['networks']) || !is_array($args['networks'])) {
        return null;
    }

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'social-icons',
    ], $args['classes']);

    foreach ($args['networks'] as $key => $network) {
        $title = $args['networks'][$key]['network']['label'];

        // 'title' should be a formatted string with a network name placeholder.
        if (!empty($args['title']) && strpos($args['title'], '%s') !== false) {
            $title = sprintf(
                $args['title'],
                $title
            );
        }

        $args['networks'][$key]['title'] = $title;
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
