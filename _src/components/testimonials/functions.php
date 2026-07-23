<?php

namespace Granola\Components\Testimonials;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'testimonials' => [],
        'ref' => \wp_unique_prefixed_id('testimonials-'),
        'transition_duration' => '500ms',
        'show_navigation' => true,
        'show_pips' => false,
        'show_counter' => true,
        'items_in_view' => 1,
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'testimonials',
        'wp-block',
    ], $args['classes']);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['testimonials'])) {
        return null;
    }

    $args['attributes']['data-testimonials-count'] = count($args['testimonials']);

    // -------------------------------------------------------------------------
    // If there is only one testimonial, return the testimonial item.
    // -------------------------------------------------------------------------
    if (count($args['testimonials']) === 1) {
        $testimonial = $args['testimonials'][0];

        $args['testimonials'] = \Granola\Component::get('quote', [
            'image' => $testimonial['image'],
            'testimonial' => $testimonial['testimonial']['testimonial'],
            'name' => $testimonial['testimonial']['name'],
            'affiliation' => $testimonial['testimonial']['affiliation'],
            'classes' => ['quote--testimonial'],
        ]);


        // Bail early - return the testimonial item.
        return $args;
    }

    // -------------------------------------------------------------------------
    // If there are multiple testimonials, return the slider.
    // -------------------------------------------------------------------------
    $slides = [];
    foreach ($args['testimonials'] as $i => $testimonial) {
        $slides[] = [
            'card' => \Granola\Component::get('element', [
                // div, not li: the slider track is no longer a <ul>, and each slide
                // gets role="group"/aria-roledescription="slide" at runtime (APG carousel).
                'el' => 'div',
                'attributes' => [
                    'id' => $args['ref'] . '-card-' . ($i + 1),
                    'class' => ['testimonials__slide'],
                ],
                'content_filter' => null,
                'content' => \Granola\Component::get('quote', [
                    'image' => $testimonial['image'],
                    'testimonial' => $testimonial['testimonial']['testimonial'],
                    'name' => $testimonial['testimonial']['name'],
                    'affiliation' => $testimonial['testimonial']['affiliation'],
                    'classes' => ['quote--testimonial'],
                ]),
            ])
        ];
    }


    // Slider args.
    $args['slider'] = [
        'ref' => $args['ref'],
        'classes' => ['testimonials__slider'],
        'slides' => $slides,
        'show_navigation' => $args['show_navigation'],
        'show_pips' => $args['show_pips'],
        'items_in_view' => $args['items_in_view'],
        'transition_duration' => $args['transition_duration'],
        'show_counter' => $args['show_counter'],
    ];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
