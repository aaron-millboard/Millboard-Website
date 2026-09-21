<?php

namespace Granola\Components\ComparisonTable;

/**
 * Compare board constructions, then rank our own ranges against real needs.
 *
 * Millboard does not compare itself to named competitor brands. The columns are
 * therefore constructions (softwood, hollow core uncapped, solid core capped,
 * wood free) and the rail below ranks our ranges by what someone is trying to
 * do, which is the substitute the brand does use.
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
        'columns' => [],
        'rows' => [],
        'footnote' => '',
        'best_for_heading' => '',
        'best_for' => [],
        'uid' => \wp_unique_id('comparison-table-'),
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'comparison-table',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    $args['columns'] = normalise_columns($args['columns']);
    $args['rows'] = normalise_rows($args['rows'], count($args['columns']));
    $args['best_for'] = normalise_best_for($args['best_for']);

    // -------------------------------------------------------------------------
    // Bail early if there is neither a table nor a rail to show.
    // -------------------------------------------------------------------------
    if (empty($args['rows']) && empty($args['best_for'])) {
        return null;
    }

    return $args;
}

/**
 * The column headings.
 *
 * @param array $columns The column rows.
 * @return array<string> The labels.
 */
function normalise_columns(array $columns): array
{
    $labels = [];

    foreach ($columns as $column) {
        $label = is_array($column) ? ($column['label'] ?? '') : $column;
        $label = trim((string) $label);

        if ($label !== '') {
            $labels[] = $label;
        }
    }

    return $labels;
}

/**
 * The body rows.
 *
 * Cells are entered one per line so a row can be pasted in rather than clicked
 * in cell by cell. Short rows are padded and long ones trimmed, so the table
 * stays structurally valid however the content is entered.
 *
 * @param array $rows The row records.
 * @param int $column_count How many columns the table has.
 * @return array The rows.
 */
function normalise_rows(array $rows, int $column_count): array
{
    if ($column_count < 1) {
        return [];
    }

    $normalised = [];

    foreach ($rows as $row) {
        $label = trim((string) ($row['label'] ?? ''));
        $raw = (string) ($row['cells'] ?? '');

        $cells = array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: []);
        $cells = array_values(array_filter($cells, static fn($cell) => $cell !== ''));

        if ($label === '' && empty($cells)) {
            continue;
        }

        $cells = array_slice($cells, 0, $column_count);
        $cells = array_pad($cells, $column_count, '');

        $normalised[] = [
            'label' => $label,
            'cells' => $cells,
        ];
    }

    return $normalised;
}

/**
 * The "best for" rail beneath the table.
 *
 * @param array $items The rail records.
 * @return array The items.
 */
function normalise_best_for(array $items): array
{
    $normalised = [];

    foreach ($items as $item) {
        $need = trim((string) ($item['need'] ?? ''));
        $range = trim((string) ($item['range'] ?? ''));

        if ($need === '' || $range === '') {
            continue;
        }

        $normalised[] = [
            'need' => $need,
            'range' => $range,
            'why' => trim((string) ($item['why'] ?? '')),
            'url' => $item['url'] ?? '',
        ];
    }

    return $normalised;
}
