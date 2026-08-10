<?php

namespace Granola\Components\Language\Switcher;

/** Theme location of the hand-built, one-item-per-locale switcher menu. */
const MENU_LOCATION = 'language-switcher';

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'uid' => \wp_unique_prefixed_id('language-switcher-'),
        'classes' => [],
        'current_language' => 'UK',
        'menu_name' => MENU_LOCATION,
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

/**
 * Repoint the language switcher's links at the current page's hreflang alternates.
 *
 * The switcher menu is built by hand on each site, with one custom link per locale
 * pointing at that locale's homepage. That meant switching locale from anywhere but
 * the homepage dumped you on the destination homepage and left you to find the
 * translated page yourself.
 *
 * The Millboard Hreflang plugin already stores the equivalent post for each locale,
 * so use the URLs it resolves. Any locale without a published equivalent keeps its
 * original homepage link, which is the previous behaviour.
 *
 * @param mixed $items The menu items. Array in practice, but other filters run here too.
 * @param mixed $menu The menu the items belong to. A WP_Term in practice.
 * @return mixed The menu items, with switcher URLs repointed where possible.
 */
function filter_menu_items_to_alternates($items, $menu)
{
    /**
     * Never rewrite in the admin. The nav menu editor reads its items through this
     * same filter and must always show the real stored URLs, or an editor could save
     * a page-specific URL back over a locale's homepage link.
     */
    if (!is_array($items) || \is_admin() || !is_switcher_menu($menu)) {
        return $items;
    }

    $alternates = get_hreflang_alternates();

    if (empty($alternates)) {
        return $items;
    }

    $current_locale = get_locale_from_url(\home_url('/'));

    foreach ($items as $item) {
        if (empty($item->url)) {
            continue;
        }

        $locale = get_locale_from_url($item->url);

        // Leave the item for the locale we're already on exactly as it is.
        if (empty($locale) || $locale === $current_locale) {
            continue;
        }

        if (!empty($alternates[$locale])) {
            $item->url = $alternates[$locale];
        }
    }

    return $items;
}

/**
 * Determines whether the given menu is the one assigned to the switcher location.
 *
 * @param mixed $menu The menu to check. A WP_Term in practice.
 * @return boolean Whether this is the language switcher's menu.
 */
function is_switcher_menu($menu): bool
{
    if (empty($menu->term_id)) {
        return false;
    }

    $locations = \get_nav_menu_locations();

    return !empty($locations[MENU_LOCATION])
        && (int) $locations[MENU_LOCATION] === (int) $menu->term_id;
}

/**
 * Retrieve the current page's hreflang alternates, keyed by locale.
 *
 * Memoised because the site header renders the switcher twice (desktop and mobile),
 * and resolving walks every site in the network.
 *
 * @return array<string, string> Locale code => permalink. Empty when there are none.
 */
function get_hreflang_alternates(): array
{
    static $alternates = null;

    if ($alternates !== null) {
        return $alternates;
    }

    $alternates = [];

    // The Millboard Hreflang plugin owns these relationships. Without it, nothing changes.
    if (!class_exists('\Millboard\Hreflang\Relations') || !class_exists('\Millboard\Hreflang\Config')) {
        return $alternates;
    }

    // Mirror the conditions the plugin uses to output its own hreflang tags.
    if (!\is_singular(\Millboard\Hreflang\Config::POST_TYPES) && !\is_front_page()) {
        return $alternates;
    }

    $post_id = (int) \get_queried_object_id();

    if (!$post_id) {
        return $alternates;
    }

    // Locale-only pages deliberately have no equivalent anywhere else.
    if (\get_post_meta($post_id, \Millboard\Hreflang\Config::META_LOCALE_ONLY, true) === '1') {
        return $alternates;
    }

    $links = \Millboard\Hreflang\Relations::resolve(\get_current_blog_id(), $post_id);

    // 'x-default' duplicates one of the real locales, so it's not a switch target.
    unset($links['x-default']);

    $alternates = $links;

    return $alternates;
}

/**
 * Extract the locale from a URL's first path segment, e.g. '/fr-fr/decking/' => 'fr-fr'.
 *
 * The network is a subdirectory multisite, so every site's URL is prefixed with its
 * locale. This gives both the current site's locale and each menu item's target locale
 * without hardcoding a list in the theme.
 *
 * @param string $url The URL to read the locale from.
 * @return string|null The lowercase locale code, or null if the URL has no locale prefix.
 */
function get_locale_from_url(string $url): ?string
{
    $path = (string) \wp_parse_url($url, PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));
    $segment = strtolower($segments[0] ?? '');

    return preg_match('/^[a-z]{2}-[a-z]{2}$/', $segment) ? $segment : null;
}
