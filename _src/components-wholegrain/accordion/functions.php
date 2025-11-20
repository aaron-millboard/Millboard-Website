<?php

namespace Granola\Components\Accordion;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'preheading' => '',
        'heading' => '',
        'cta' => [],
        'allow_multiple' => true, // Default to allowing multiple open items
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'accordion',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // ---------------------------------------
    // Set up main wrapper attributes
    // ---------------------------------------
    $args['attributes']['data-allow-multiple'] = $args['allow_multiple'] ? 'true' : 'false';



    if (!empty($args['description'])) {
        $args['description'] = [
            'content' => $args['description'],
            'classes' => [
                'page-header__description-text'
            ],
        ];
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
                'accordion__header__cta',
                'g-button'
            ],
        ];
    }

    // ---------------------------------------
    // Set up the innerblocks tag.
    // ---------------------------------------
    $innerblocks_attrs = [
        'class' => 'accordion__items',
        'allowedBlocks' => ['acf/accordion-item'],
        'template' => [
            ['acf/accordion-item'],
        ],
        'templateLock' => false,
    ];

    $args['innerblocks_tag'] = \Granola\Helpers::build_inner_blocks_tag($innerblocks_attrs);

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
