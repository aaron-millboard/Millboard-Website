<?php

namespace Granola\Components\InstallerAbout;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'body' => '',
        'tags' => [],
        'image' => null,
        'facts' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'installer-about',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Bail early - return null for no output (unless previewing in the editor).
    // -------------------------------------------------------------------------
    if (empty($args['heading']) && empty($args['body']) && empty($args['is_preview'])) {
        return null;
    }

    $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : \get_the_ID();

    // Heading defaults to "About [installer name]".
    if (empty($args['heading'])) {
        /* translators: %s: installer name. */
        $args['heading'] = sprintf(\__('About %s', 'granola'), \get_the_title($post_id));
    }

    // -------------------------------------------------------------------------
    // Decide the right-hand aside: a facts table takes priority over an image.
    // -------------------------------------------------------------------------
    $facts = array_filter((array) $args['facts'], function ($row) {
        return !empty($row['label']) || !empty($row['value']);
    });
    $args['facts'] = $facts;

    if (!empty($facts)) {
        $args['aside'] = 'facts';
    } elseif (!empty($args['image'])) {
        $args['aside'] = 'image';
    } else {
        $args['aside'] = 'none';
    }

    $args['classes'][] = 'installer-about--' . $args['aside'];

    // Normalise tag rows to plain strings.
    $args['tags'] = array_values(array_filter(array_map(function ($row) {
        return isset($row['tag']) ? trim($row['tag']) : '';
    }, (array) $args['tags'])));

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
