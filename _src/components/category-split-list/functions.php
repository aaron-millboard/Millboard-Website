<?php

namespace Granola\Components\CategorySplitList;

/**
 * A column of framing on the left, a list of short facts on the right.
 *
 * Serves two sections of the category design that are the same pattern: "Why
 * choose Millboard", where the left column carries an image, and
 * "Certification and specification", where it carries a paragraph and a link
 * to the brochures library.
 *
 * Not a change to list-values or list-specifications. Those render 138 and 257
 * published pages on en-gb and neither has a left column, an image or a link.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'meta_prefix' => '',
        'heading' => '',
        'intro' => '',
        'image_id' => 0,
        'link' => [],
        'items' => [],
        // The certification section sits on Mist in the design, the benefits
        // one does not, so the page says which.
        'background' => '',
        'uid' => \wp_unique_id('category-split-list-'),
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge(
        [
            'category-split-list',
            'wp-block',
            'alignfull',
        ],
        $args['background'] === 'mist' ? ['category-split-list--mist'] : [],
        $args['classes']
    );

    $args['items'] = normalise_items($args['items']);

    // -------------------------------------------------------------------------
    // Bail early if there is no list. The left column alone is not a section.
    // -------------------------------------------------------------------------
    if (empty($args['items'])) {
        return null;
    }

    return $args;
}

/**
 * Drop rows with nothing to say.
 *
 * @param array $items The rows.
 * @return array The items.
 */
function normalise_items(array $items): array
{
    $normalised = [];

    foreach ($items as $item) {
        $title = trim((string) ($item['title'] ?? ''));
        $detail = trim((string) ($item['detail'] ?? ''));

        if ($title === '' && $detail === '') {
            continue;
        }

        $normalised[] = [
            'title' => $title,
            'detail' => $detail,
        ];
    }

    return $normalised;
}
