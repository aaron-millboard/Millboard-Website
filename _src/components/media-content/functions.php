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
    // Heading inset.
    // -------------------------------------------------------------------------
    if (!empty($args['heading']) && !empty($args['heading_inset'])) {
        $args['classes'][] = 'media-content--has-heading-inset';
        $args['heading_class'] = 'is-style-typestyle-h1';
        $args['heading'] = \Granola\Component::get('element', [
            'el' => 'span',
            'content' => $args['heading'],
            'classes' => ['media-object__heading-primary'],
        ]) . \Granola\Component::get('element', [
            'el' => 'span',
            'content' => $args['heading_inset'],
            'classes' => ['media-object__heading-secondary'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Buttons.
    // -------------------------------------------------------------------------
    // -------------------------------------------------------------------------
    if (!empty($args['buttons'])) {
        $args['buttons'] = array_map(function ($button) {
            $weight = $button['weight'] ?? 'primary';
            $button = $button['button'];
            return [
                'url' => $button['url'],
                'target' => $button['target'],
                'content' => $button['title'],
                'classes' => [
                    $weight === 'primary' ? 'g-button--primary' : 'g-button--secondary',
                ]
            ];
        }, $args['buttons']);
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
