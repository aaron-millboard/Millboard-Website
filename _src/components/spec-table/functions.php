<?php

namespace Granola\Components\SpecTable;

/**
 * Specification table: a heading row and rows of cells, any cell optionally a
 * link to the sheet behind it.
 *
 * Looks like the comparison-table block (Mist band, olive rule over a small
 * meta line, 14px cells, 11px column heads). The difference is that a cell can
 * be a link and the first column can carry a heading of its own, so a row can
 * point at its document. comparison-table is used on live pages and is left
 * alone.
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
        'corner' => '',
        'columns' => [],
        'rows' => [],
    ], $args);

    $args['columns'] = array_values(array_map(static function ($column) {
        return (string) ($column['label'] ?? '');
    }, (array) $args['columns']));

    $args['rows'] = array_values(array_filter((array) $args['rows'], static function ($row) {
        return !empty($row['label']);
    }));

    if (empty($args['columns']) || empty($args['rows'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Cells: exactly one per column, so a short row never breaks the grid.
    // -------------------------------------------------------------------------
    foreach ($args['rows'] as $key => $row) {
        $cells = array_values((array) ($row['cells'] ?? []));
        $normalised = [];

        foreach ($args['columns'] as $index => $unused) {
            $cell = $cells[$index] ?? [];
            $normalised[] = [
                'text' => (string) ($cell['text'] ?? ''),
                'link' => !empty($cell['link']['url']) ? $cell['link'] : null,
            ];
        }

        $args['rows'][$key]['cells'] = $normalised;
    }

    // -------------------------------------------------------------------------
    // Required classes. Mist unless the editor picks another background.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'spec-table',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // The picked colour's class is added from background_color; the default only when nothing is picked.
    if (empty($args['background_color']) || $args['background_color'] === 'none') {
        $args['classes'][] = 'has-brand-1-background-color';
        $args['classes'][] = 'has-background';
    }

    return $args;
}
