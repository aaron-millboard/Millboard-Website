<?php

namespace Granola\Components\Language\Switcher;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'uid' => \wp_unique_prefixed_id('language-switcher-'),
        'classes' => [],
        'current_language' => 'EN',
        'menu_name' => 'language-switcher',
    ], $args);


    // Bail early if the language switcher is not visible./
    if (!\has_nav_menu($args['menu_name'])) {
        return null;
    }

    $current_locale = \get_locale();
    $short_lang = strtoupper(substr($current_locale, 0, 2));

    $args['current_language'] = $short_lang;

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'language-switcher',
    ], $args['classes']);

    // Set the button arguments.
    $args['button'] = [
        'content' => get_button_content($args['current_language']),
        'classes' => ['language-switcher__button'],
        'attributes' => [
            'aria-label' => _x('Switch language', 'Language Switcher Button Screen Reader Text', 'granola'),
            'aria-controls' => $args['uid'],
            'aria-expanded' => 'false',
        ],
    ];

    // Set the items attributes.
    $args['items_attributes'] = [
        'id' => $args['uid'],
        'aria-hidden' => 'true',
        'hidden' => 'hidden',
        'class' => ['language-switcher__items'],
    ];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Get the button content.
 *
 * @param string $current_language The current language.
 * @param bool $is_expanded Whether the button is expanded.
 * @return string The button content.
 */
function get_button_content(string $current_language, bool $is_expanded = false): string
{
    $parts = [];

    $parts[] = \Granola\Component::get('element', [
        'el' => 'span',
        'classes' => ['language-switcher__button__icon', 'language-switcher__button__icon--globe'],
        'content' => '',
    ]);

    $parts[] = $current_language;

    $parts[] = \Granola\Component::get('element', [
        'el' => 'span',
        'classes' => ['language-switcher__button__icon', 'language-switcher__button__icon--chevron'],
        'content' => '',
    ]);

    return implode('', $parts);
}
