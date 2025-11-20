<?php

namespace Granola\Components\MediaContent;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'subheading' => '',
        'content' => '',
        'video' => [],
        'image' => '',
        'media_type' => 'image',
        'media_side' => 'left',
        'image' => [],
        'media' => '',
        // Media Object arguments.
        'orientation' => 'horizontal',
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'media-content',
        'wp-block',
    ], $args['classes']);

    $args['media_position'] = $args['media_side'] === 'left' ? 'before' : 'after';

    // Handle buttons.
    if (!empty($args['button_1'])) {
        $args['buttons'][] = [
            'url' => $args['button_1']['url'],
            'target' => $args['button_1']['target'],
            'content' => $args['button_1']['title'],
        ];
    }

    // -------------------------------------------------------------------------
    // Set image args if one exists.
    // -------------------------------------------------------------------------
    if (!empty($args['image'])) {
        $args['image']['size'] = 'super';
        $args['image']['sizes'] = '(max-width: 768px) 100vw, 50vw';
        $args['media'] = $args['image'];
    }

    // -------------------------------------------------------------------------
    // Set video args if one exists.
    // -------------------------------------------------------------------------
    if (!empty($args['video'])) {
        $args['video'] = [
            'video' => $args['video'],
            'image' => $args['image'],
        ];
    }


    // -------------------------------------------------------------------------
    // Smaller sizes.
    // -------------------------------------------------------------------------
    if ($args['align'] === 'center') {
        $args['heading_class'] = 'is-style-typestyle-h4';
    }
    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
