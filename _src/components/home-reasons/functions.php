<?php

namespace Granola\Components\HomeReasons;

/**
 * The brand icon for each reason.
 *
 * These are the Millboard icon set as supplied, held in the media library.
 * Matched on file name rather than attachment id because the id differs on
 * every subsite: Made-In-Britain-Icon is 221 on en-gb, 5904 on en-us and 9084
 * on de-de, so an id baked in here would draw the wrong picture on five of the
 * six sites.
 */
function icons(): array
{
    return [
        'factory' => 'Made-In-Britain-Icon.svg',
        'hand'    => 'Hand-Moulded-Icon.svg',
        'grain'   => 'Natural-Wood-Look-Icon.svg',
        'shield'  => 'Durable-Icon.svg',
        'refresh' => 'Maintenance-Icon.svg',
        'recycle' => 'Recycled-Icon.svg',
        'board'   => 'Wood-Free-Icon.svg',
        'medal'   => 'Quality-Assurance-Icon.svg',
        'clock'   => 'Long-Lifespan-Icon.svg',
    ];
}

/**
 * The attachment id for an icon on the site being rendered.
 *
 * Looked up once per file per request. A miss returns 0 and the item simply
 * renders without an icon, which is better than a broken image.
 */
function icon_id(string $file): int
{
    static $cache = [];

    $key = get_current_blog_id() . ':' . $file;

    if (!isset($cache[$key])) {
        global $wpdb;

        $cache[$key] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s
             ORDER BY post_id ASC LIMIT 1",
            '%/' . $wpdb->esc_like($file)
        ));
    }

    return $cache[$key];
}

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'eyebrow' => null,
        'heading' => null,
        'reasons' => [],
        'classes' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'home-reasons',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Reasons.
    //
    // A row with no title is an empty repeater row, which is what an editor
    // leaves behind after adding one and changing their mind. Rendering it
    // would put a stray icon in the grid.
    //
    // The stagger cycles every third item, so a row arrives left to right and
    // the next row starts again rather than the ninth card waiting a second
    // and a half. It is keyed to the position in the list rather than to the
    // rendered column, because the column count changes with the viewport and
    // CSS cannot tell PHP which one an item landed in.
    // -------------------------------------------------------------------------
    $available = icons();

    $args['reasons'] = array_values(array_filter((array) $args['reasons'], function ($reason) {
        return !empty($reason['title']) || !empty($reason['text']);
    }));

    $args['reasons'] = array_map(function ($reason, $index) use ($available) {
        $key = $reason['icon'] ?? '';

        $reason['icon_id'] = isset($available[$key]) ? icon_id($available[$key]) : 0;
        $reason['delay'] = ($index % 3) * 126;

        return $reason;
    }, $args['reasons'], array_keys($args['reasons']));

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
