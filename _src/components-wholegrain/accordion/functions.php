<?php

namespace Granola\Components\Accordion;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'content' => '',
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'accordion',
        'wp-block',
    ], $args['classes']);

    if (!empty($args['heading'])) {
        $args['heading'] = [
            'content' => $args['heading'],
            'classes' => ['accordion__heading'],
        ];
    }

    if (!empty($args['accordion_items'])) {
        $args['accordion_items'] = array_map(function ($item) {
            $item['panel_id'] = wp_unique_id('accordion-panel-');
            $item['button_id'] = wp_unique_id('accordion-button-');

            $item['button'] = [
                'id' => $item['button_id'],
                'classes' => [
                    'accordion__item__header',
                    'js-accordion-button'
                ],
                'attributes' => [
                    'aria-expanded' => 'false',
                    'aria-controls' => $item['panel_id'],
                ],
                'content' => \Granola\Component::get('heading', [
                    'el' => 'h3',
                    'content' => $item['title'],
                    'classes' => ['accordion__item__heading']
                ]),
            ];

            $item['panel_attributes'] = [
                'id' => $item['panel_id'],
                'class' => \Granola\Helpers::build_classes([
                    'accordion__item__panel',
                    'js-expandable-element'
                ]),
                'hidden' => true,
                'aria-hidden' => 'true',
                'aria-labelledby' => $item['button_id']
            ];

            return $item;
        }, $args['accordion_items']);
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
