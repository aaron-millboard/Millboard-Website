<?php

namespace Granola\Components\Slider;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [],
        'slides' => [],
        'navigation' => [],
        'pips' => [],
        'show_navigation' => true,
        'show_pips' => true,
        'show_counter' => false,
        'items_in_view' => 1,
        'enable_keyboard' => true,
        'enable_touch' => true,
        'respect_reduced_motion' => true,
        'transition_duration' => '300ms',
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'slider',
    ], $args['classes']);

    $args['total_slides'] = count($args['slides']);

    // -------------------------------------------------------------------------
    // Data attributes for JavaScript configuration.
    // -------------------------------------------------------------------------
    $args['attributes'] = array_merge([
        'data-items-in-view' => $args['items_in_view'],
        'data-enable-keyboard' => $args['enable_keyboard'] ? 'true' : 'false',
        'data-enable-touch' => $args['enable_touch'] ? 'true' : 'false',
        'data-respect-reduced-motion' => $args['respect_reduced_motion'] ? 'true' : 'false',
        'data-transition-duration' => $args['transition_duration'],
        'style' => [
            '--slider-transition-duration' => $args['transition_duration'],
            '--slider-items-in-view' => $args['items_in_view'],
            '--slider-total-slides' => $args['total_slides'],
        ],
    ], $args['attributes']);

    // -------------------------------------------------------------------------
    // Build track attributes.
    // -------------------------------------------------------------------------
    $args['track_attributes'] = [
        'class' => ['slider__track', 'list-reset--hard'],
        'role' => 'tablist',
        'aria-label' => __('Slider', 'granola'),
        'style' => [
            '--slider-transition-duration: ' . $args['transition_duration'],
        ],
    ];

    // -------------------------------------------------------------------------
    // Build slides attributes.
    // -------------------------------------------------------------------------
    foreach ($args['slides'] as $index => $slide) {
        // $args['slides'][$index]['card']->args['classes'][] = 'slider__slide';
        $args['slides'][$index]['card']->args['attributes']['class'][] = 'slider__slide';
        $args['slides'][$index]['card']->args['attributes']['id'] = $args['ref'] . '-slide-' . ($index + 1);
        $args['slides'][$index]['card']->args['attributes']['role'] = 'tabpanel';
        $args['slides'][$index]['card']->args['attributes']['aria-label'] = __('Slide', 'granola') . ' ' . ($index + 1) . ' of ' . $args['total_slides'];
    }

    // Build pips button components.
    foreach ($args['slides'] as $index => $slide) {
        $args['pips'][] = [
            'classes' => ['slider__pip', 'g-button'],
            'role' => 'tab',
            'aria-label' => sprintf(
                // translators: slide number
                \__('Go to slide %s', 'granola'),
                ($index + 1)
            ),
            'data-index' => $index,
            'aria-selected' => $index === 0 ? 'true' : 'false',
            'visually_hidden_text' => true,
            'aria-controls' => $args['ref'] . '-slide-' . ($index + 1),
            'content' => sprintf(
                // translators: slide number
                \__('Slide %s', 'granola'),
                ($index + 1)
            ),
        ];
    }

    // Build navigation - 2 buttons for previous and next.
    foreach (range(0, 1) as $index) {
        $aria_label = ($index === 0) ? \__('Previous slide', 'granola') : \__('Next slide', 'granola');

        $args['navigation'][] = [
            'classes' => [
                'slider__navigation',
                'g-button',
                'g-button--square',
                'g-button--arrow',
                'slider__navigation--' . ($index === 0 ? 'previous' : 'next'),
            ],
            'role' => 'tab',
            'aria-label' => $aria_label,
            'content_filter' => false,
            'content' => ' ',
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
