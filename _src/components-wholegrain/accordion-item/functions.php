<?php

namespace Granola\Components\AccordionItem;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'content' => '',
        'title' => '',
        'panel_id' => wp_unique_id('accordion-panel-'),
        'button_id' => wp_unique_id('accordion-button-'),
        'is_opened' => false,
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'accordion__item',
        'wp-block',
    ], $args['classes']);

    // ---------------------------------------
    // Assign unique IDs and prepare button & panel attributes
    // ---------------------------------------
    $args['button'] = [
        'id' => $args['button_id'],
        'classes' => [
            'accordion__item__trigger',
            'js-accordion-button'
        ],
        'attributes' => [
            'aria-expanded' => $args['is_opened'] ? 'true' : 'false',
            'aria-controls' => $args['panel_id'],
        ],
        'content' => \Granola\Component::get('heading', [
            'el' => 'h3',
            'content' => $args['title'],
            'classes' => ['accordion__item__heading']
        ]),
    ];

    // ---------------------------------------
    // Prepare panel attributes
    // ---------------------------------------
    $args['panel_attributes'] = [
        'id' => $args['panel_id'],
        'class' => \Granola\Helpers::build_classes([
            'accordion__item__panel',
            'js-expandable-element'
        ]),
        'aria-labelledby' => $args['button_id']
    ];

    if (!$args['is_opened']) {
        $args['panel_attributes']['hidden'] = 'true';
        $args['panel_attributes']['aria-hidden'] = 'true';
    }

    if (!empty($args['cta'])) {
        $args['cta'] = [
            'title'    => $args['cta']['title'] ?? '',
            'url'      => $args['cta']['url'] ?? '',
            'attributes' => [
                'target' => $args['cta']['target'] ?? '',
                'rel'    => $args['cta']['rel'] ?? '',
            ],
            'classes' => [
                'accordion__item__panel__inner__cta',
                'g-button'
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
