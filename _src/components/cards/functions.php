<?php

namespace Granola\Components\Cards;

/**
 * Handle shared card args logic.
 *
 * @param array $args The args.
 * @return array The filtered args.
 */
function handle_shared_card_args_logic(array $args): array
{
    // ---------------------------------------
    // Set up the button.
    // ---------------------------------------
    if (!empty($args['button'])) {
        $args['buttons'][] = array_merge([
            'classes' => ['g-button'],
        ], $args['button']);
    }

    // ---------------------------------------
    // Set up the heading.
    // ---------------------------------------
    if (!empty($args['heading'])) {
        $args['heading'] = [
            'content' => $args['heading'],
            'classes' => [
                'cards__heading',
                (isset($args['heading_class']) && !empty($args['heading_class'])) ? $args['heading_class'] : '',
            ],
        ];
    }

    // ---------------------------------------
    // Set up the TOC title.
    // ---------------------------------------
    if (isset($args['toc_title']) && !empty($args['toc_title'])) {
        $args['attributes']['data-toc-title'] = $args['toc_title'];
    }

    // Calculate the best column count.
    $args['inner_attributes']['style']['--cards--best-column-fit'] = best_column_count(
        null,
        (int) $args['columns']
    );


    // ---------------------------------------
    // Return the filtered args.
    // ---------------------------------------
    return $args;
}


/**
 * Best column count.
 *
 * @param int|null $n The number of items.
 * @param int|null $columns The number of columns.
 * @return int The best column count for our desired layout.
 */
function best_column_count(int|null $n, int|null $columns): int
{
    if (!empty($columns)) {
        return $columns;
    }

    if (empty($n)) {
        return 2;
    }

    if ($n <= 0) {
        return 2; // default safely
    }

    $holes2 = (2 - ($n % 2)) % 2;
    $holes3 = (3 - ($n % 3)) % 3;

    if ($holes2 < $holes3) {
        return 2;
    }
    if ($holes3 < $holes2) {
        return 3;
    }
    return 3; // tie-breaker: prefer 3
}
