<?php

namespace Granola\Components\SpecReferences;

/**
 * Independent standards and guidance a specifier page refers to: a list of
 * links to outside bodies, each with a line saying what it covers and where it
 * comes from.
 *
 * The links open in a new tab and say so, and the host of each is shown so a
 * reader knows they are leaving Millboard before they click.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [],
        'preheading' => '',
        'heading' => '',
        'intro' => '',
        'items' => [],
    ], $args);

    $args['items'] = array_values(array_filter(array_map(static function ($item) {
        $link = $item['link'] ?? [];

        if (empty($link['url']) || empty($link['title'])) {
            return null;
        }

        $host = (string) parse_url($link['url'], PHP_URL_HOST);

        return [
            'title' => (string) $link['title'],
            'url' => (string) $link['url'],
            'source' => (string) preg_replace('/^www\./', '', $host),
            'description' => trim((string) ($item['description'] ?? '')),
        ];
    }, (array) $args['items'])));

    if (empty($args['items'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Required classes. Mist unless the editor picks another background.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'spec-references',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    if (empty($args['background_color']) || $args['background_color'] === 'none') {
        $args['classes'][] = 'has-brand-1-background-color';
        $args['classes'][] = 'has-background';
    }

    return $args;
}
