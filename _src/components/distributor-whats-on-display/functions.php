<?php

namespace Granola\Components\DistributorWhatsOnDisplay;

/**
 * What a distributor / showroom / experience centre has on display.
 *
 * Parsed from the record's free-text "What's on display" field, so branches can be
 * kept current without touching the page. The design groups the chips under
 * category headings (Decking, Cladding), which a textarea expresses as a line
 * ending in a colon:
 *
 *     Decking:
 *     Enhanced Grain - Smoked Oak
 *     Weathered Oak - Embered
 *     Cladding:
 *     Board & Batten+ - Burnt Cedar
 *
 * Records with no headings render as one flat group, which is what the existing
 * free-text entries look like.
 *
 * Returns null when the field is empty, which is currently every production
 * record, so the section hides rather than rendering an empty grid.
 */
function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'intro' => '',
        'image' => null,
    ], $args);

    $args['classes'] = array_merge([
        'distributor-whats-on-display',
        'wp-block',
    ], $args['classes']);

    $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : \get_the_ID();
    if (!$post_id) {
        return empty($args['is_preview']) ? null : $args;
    }

    $args['groups'] = parse_display((string) \get_field('display_collections', $post_id));

    if (empty($args['groups']) && empty($args['is_preview'])) {
        return null;
    }

    if (empty($args['heading'])) {
        $args['heading'] = \__("What's on display", 'granola');
    }

    // The record's display_photo field returns an array (return_format "array"),
    // while this block's own image field returns an ID, so both are normalised. The
    // image component wants an ID and renders nothing when handed the array.
    $args['image'] = \Granola\Components\DistributorContactCard\attachment_id(
        !empty($args['image']) ? $args['image'] : \get_field('display_photo', $post_id)
    );

    $args['classes'][] = !empty($args['image'])
        ? 'distributor-whats-on-display--has-image'
        : 'distributor-whats-on-display--no-image';

    return $args;
}

/**
 * Turn the textarea into groups of chips.
 *
 * @return array<int, array{label: string, items: array<int, string>}>
 */
function parse_display(string $raw): array
{
    if (trim($raw) === '') {
        return [];
    }

    $groups = [];
    $current = ['label' => '', 'items' => []];

    foreach (preg_split('~[\r\n]+~', $raw) as $line) {
        $line = trim($line, " \t-*");

        if ($line === '') {
            continue;
        }

        // A line ending in a colon opens a new group.
        if (substr($line, -1) === ':') {
            if ($current['items']) {
                $groups[] = $current;
            }

            $current = ['label' => rtrim($line, ": \t"), 'items' => []];
            continue;
        }

        // Otherwise the line is one or more comma separated items. Splitting on
        // commas keeps the older single-line entries working, where a branch
        // listed everything on one row.
        foreach (explode(',', $line) as $item) {
            $item = trim($item, " \t-*");

            if ($item !== '') {
                $current['items'][] = $item;
            }
        }
    }

    if ($current['items']) {
        $groups[] = $current;
    }

    return $groups;
}
