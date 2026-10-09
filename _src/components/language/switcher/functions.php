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
        'current_language' => 'UK',
        'menu_name' => 'language-switcher',
    ], $args);


    // Bail early if the language switcher is not visible./
    if (!\has_nav_menu($args['menu_name'])) {
        return null;
    }

    $args['current_language'] = get_language_from_url();

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
 * Get the current language code for the switcher label.
 *
 * @return string The current language code (2-letter uppercase).
 */
function get_language_from_url() {
    // Get request URI (e.g. /de-de/some-page/)
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $request_path = (string) \wp_parse_url($request_uri, PHP_URL_PATH);

    // Trim slashes and split path segments
    $segments = explode('/', trim($request_path, '/'));

    // First segment should be the language (e.g. de-de)
    if (!empty($segments[0]) && preg_match('/^[a-z]{2}-[a-z]{2}$/i', $segments[0])) {
        $lang_code = strtoupper(substr($segments[0], -2));
        return $lang_code === 'GB' ? 'UK' : ($lang_code === 'IE' ? 'IRE' : $lang_code);
    }

    // Fallback language
    return 'UK';
}

// Usage
$current_language = get_language_from_url();

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

    $parts[] = \Granola\Component::get('element', [
        'el' => 'span',
        'classes' => ['language-switcher__button__label'],
        'content' => $current_language,
    ]);

    $parts[] = \Granola\Component::get('element', [
        'el' => 'span',
        'classes' => ['language-switcher__button__icon', 'language-switcher__button__icon--chevron'],
        'content' => '',
    ]);

    return implode('', $parts);
}
