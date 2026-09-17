<?php

namespace Granola\Components\HomeReasons;

/**
 * The line icons this block can draw.
 *
 * Held in code rather than offered as an upload. The brand guide is explicit
 * that icons are "small, borderless and line-based" and should not dominate a
 * layout, and an upload field is how a layout ends up with nine different
 * illustration styles. Each entry is the inner markup of a 24x24 viewBox drawn
 * on a 1.4 stroke.
 */
function icons(): array
{
    return [
        'factory' => '<path d="M4 20V9l8-5 8 5v11"></path><path d="M9 20v-6h6v6"></path>',
        'hand' => '<path d="M14.5 4.5 19 9l-9.5 9.5L4 20l1.5-5.5Z"></path><path d="m12.5 6.5 5 5"></path>',
        'grain' => '<path d="M12 3c4 4 4 14 0 18-4-4-4-14 0-18Z"></path><path d="M12 3c-4 4-4 14 0 18"></path>',
        'shield' => '<path d="M12 3.5 19.5 7v6c0 4-3.2 6.6-7.5 7.8C7.7 19.6 4.5 17 4.5 13V7Z"></path>',
        'refresh' => '<path d="M5 12a7 7 0 0 1 12-5"></path><path d="M19 12a7 7 0 0 1-12 5"></path><path d="M17 3v4h-4M7 21v-4h4"></path>',
        'recycle' => '<path d="M7 8 4.5 12l2 3.5"></path><path d="M12 4.5 14.5 8 12 11.5"></path><path d="M19.5 15.5 17 12l-3.5-.5"></path><circle cx="12" cy="12" r="9"></circle>',
        'board' => '<path d="M4 7h16v10H4z"></path><path d="M4 11h16M4 14h16"></path>',
        'medal' => '<circle cx="12" cy="10" r="5.5"></circle><path d="m8.5 15-1 6 4.5-2.4L16.5 21l-1-6"></path>',
        'clock' => '<circle cx="12" cy="12" r="8.5"></circle><path d="M12 7v5.4l3.4 2"></path>',
    ];
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

        $reason['icon_markup'] = $available[$key] ?? '';
        $reason['delay'] = ($index % 3) * 126;

        return $reason;
    }, $args['reasons'], array_keys($args['reasons']));

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
