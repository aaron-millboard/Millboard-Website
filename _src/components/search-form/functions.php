<?php

namespace Granola\Components\SearchForm;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'input_id' => \wp_unique_id('search-form-'),
        'classes' => [],
        'attributes' => [],
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'search-form',
    ], $args['classes']);

    // ---------------------------------------
    // Default attributes.
    // ---------------------------------------
    $args['attributes'] = array_merge([
        'autocomplete' => 'off',
        'method' => 'get',
        'action' => \home_url('/'),
        'role' => 'search',
    ], $args['attributes']);

    $args['submit_button'] = [
        'content' => \__('Search', 'granola'),
        'type' => 'submit',
        'classes' => [
            'search-form__submit',
            'g-button',
        ],
        'attributes' => [
            'aria-label' => \__('Submit search', 'granola'),
        ],
    ];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
