<?php

namespace Granola\Components\CarbonBadge;

function filter_args(array $args): array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [],
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'carbon-badge',
        // 'theme--dark',
    ], $args['classes']);

    $args['link'] = [
        'content' => __('Website Carbon', 'granola'),
        'url' => 'https://www.websitecarbon.com/',
        'classes' => [
            'carbon-badge__link',
        ],
        'attributes' => [
            'rel' => 'noopener',
        ],
    ];

    $args['placeholder'] = sprintf(
        // translators: 1: opening subscript html tag. 2: closing subscript html tag. 3: ellipsis.
        __('Measuring CO%1$s2%2$s%3$s', 'granola'),
        '<sub>',
        '</sub>',
        '&hellip;'
    );

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Localize Carbon Badge strings that will be output using JavaScript.
 *
 * These strings can then be accessed in JS via the global carbonBadgeL10nObject variable.
 *
 * @return void
 */
function localize_carbon_badge()
{
    \wp_localize_script(
        'granola-scripts',
        'carbonBadgeL10nObject',
        [
            'result' => sprintf(
                // translators: 1: opening subscript html tag. 2: closing subscript html tag.
                __('{grams}g of CO%1$s2%2$s', 'granola'),
                '<sub>',
                '</sub>',
            ),
            'cleaner' => __('Cleaner than {percent}% of pages tested', 'granola'),
        ]
    );
}
