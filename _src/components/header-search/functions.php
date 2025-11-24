<?php

namespace Granola\Components\HeaderSearch;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'input_id' => \wp_unique_id('header-search-'),
        'background_color' => 'brand-2',
        'classes' => [],
        'attributes' => [],
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'header-search',
    ], $args['classes']);

    // ---------------------------------------
    // Default attributes.
    // ---------------------------------------
    $args['attributes'] = array_merge([
        'autocomplete' => 'off',
        'method' => 'get',
        'action' => \home_url('/'),
        'role' => 'search',
        'hidden' => 'hidden',
        'aria-hidden' => 'true',
    ], $args['attributes']);

    $args['submit_button'] = [
        'content' => \__('Search', 'granola'),
        'classes' => [
            'header-search__submit',
            'g-button',
        ],
        'attributes' => [
            'type' => 'submit',
            'aria-label' => \__('Submit search', 'granola'),
        ],
    ];

    $args['close_button'] = [
        'content' => sprintf('%s <span class="header-search__close-icon" aria-hidden="true">&times;</span>', \__('Close', 'granola')),
        'classes' => [
            'header-search__close'
        ],
        'attributes' => [
            'type' => 'button',
            'aria-label' => \__('Close search', 'granola'),
            'aria-controls' => $args['attributes']['id'] ?? 'site-header-search-form',
            'aria-expanded' => 'true',
        ],
    ];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
