<?php

namespace Granola\Components\Gallery\Items;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'image_rows' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'gallery__list',
        'list-reset--hard',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Bail early - return null for no output.
    // -------------------------------------------------------------------------
    if (empty($args['image_rows'])) {
         return null;
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Caption text, wrapped in a link when it has a URL.
 */
function caption_html(string $text, string $url = ''): string
{
    $text = esc_html($text);

    return $url && $text !== '' ? sprintf('<a href="%s">%s</a>', esc_url($url), $text) : $text;
}
