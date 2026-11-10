<?php

namespace Granola\Components\SpecDownloads;

/*
 * Typed document library: one row per file with its type, format and size, an
 * edition or test date, and View and Download actions. From six files the rows
 * can be filtered by type.
 *
 * The header, the action buttons and the icons are the downloads block's own
 * classes, so they look the same. What the downloads block cannot do is the type
 * label, the meta line, the filter tabs, a link-only row and a View action that
 * is dropped for files a browser cannot show. downloads is used on live pages
 * and is left alone.
 */

const TYPES = [
    'specification-sheet' => 'Specification sheet',
    'technical-sheet' => 'Technical sheet',
    'loading-table' => 'Loading table',
    'test-report' => 'Test report',
    'installation-guide' => 'Installation guide',
    'drawing' => 'Drawing',
    'brochure' => 'Brochure',
    'care-guide' => 'Care guide',
    'warranty' => 'Warranty',
    'certificate' => 'Certificate',
];

/** Filter tabs appear from this many documents. */
const TABS_FROM = 6;

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
        'cta' => [],
        'files' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // Rows.
    // -------------------------------------------------------------------------
    $rows = [];

    foreach ((array) $args['files'] as $file) {
        $row = build_row((array) $file);

        if ($row) {
            $rows[] = $row;
        }
    }

    if (empty($rows)) {
        return null;
    }

    $args['rows'] = $rows;

    // -------------------------------------------------------------------------
    // Filter tabs: only when there is enough to filter and more than one type.
    // -------------------------------------------------------------------------
    $types = [];

    foreach ($rows as $row) {
        $types[$row['type']] = $row['type_label'];
    }

    $args['tabs'] = (count($rows) >= TABS_FROM && count($types) > 1) ? $types : [];

    // -------------------------------------------------------------------------
    // Header button.
    // -------------------------------------------------------------------------
    if (!empty($args['cta']['url'])) {
        $args['cta'] = [
            'title' => $args['cta']['title'] ?? '',
            'url' => $args['cta']['url'],
            'attributes' => [
                'target' => $args['cta']['target'] ?? '',
                'rel' => $args['cta']['rel'] ?? '',
            ],
            'classes' => ['downloads__cta', 'g-button'],
        ];
    } else {
        $args['cta'] = [];
    }

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'spec-downloads',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    if (!empty($args['background']) && $args['background'] !== 'none') {
        $args['classes'][] = 'has-' . $args['background'] . '-background-color';
        $args['classes'][] = 'has-background';
    }

    return $args;
}

/**
 * One document, as the row the template draws. Null when it has neither a file
 * nor a link to point at.
 */
function build_row(array $file): ?array
{
    $title = trim((string) ($file['title'] ?? ''));
    $type = isset(TYPES[$file['type'] ?? '']) ? $file['type'] : 'specification-sheet';
    $attachment = is_array($file['file'] ?? null) ? $file['file'] : [];
    $link = is_array($file['link'] ?? null) ? $file['link'] : [];

    if ($title === '' || (empty($attachment['url']) && empty($link['url']))) {
        return null;
    }

    $meta = [];
    $actions = [];

    if (!empty($attachment['url'])) {
        $extension = strtoupper((string) pathinfo((string) ($attachment['filename'] ?? $attachment['url']), PATHINFO_EXTENSION));
        $is_pdf = ($attachment['subtype'] ?? '') === 'pdf' || $extension === 'PDF';
        $bytes = (int) ($attachment['filesize'] ?? 0);

        $meta[] = $extension;

        if ($bytes > 0) {
            $meta[] = format_file_size($bytes);
        }

        if ($is_pdf) {
            $actions[] = action('view', 'View', $attachment['url'], $title);
        }

        $actions[] = action('download', 'Download', $attachment['url'], $title);
    } else {
        $actions[] = action('view', 'View', $link['url'], $title, $link['target'] ?? '');
    }

    if (!empty($file['edition'])) {
        $meta[] = (string) $file['edition'];
    }

    return [
        'type' => $type,
        'type_label' => TYPES[$type],
        'title' => $title,
        'meta' => implode(' · ', $meta),
        'actions' => $actions,
    ];
}

/**
 * A View or Download control, built the way the downloads block builds its own
 * so the same classes and icons apply.
 */
function action(string $kind, string $label, string $url, string $title, string $target = ''): array
{
    $is_view = $kind === 'view';

    return [
        'content' => '<span>' . \esc_html($label) . '</span>',
        'icon' => \Granola\SVG::get($is_view ? 'icons-custom/view.svg' : 'icons-custom/download.svg'),
        'url' => $url,
        'attributes' => $is_view
            ? ['target' => $target ?: '_blank', 'rel' => 'noopener noreferrer', 'aria-label' => $label . ' ' . $title . ' (' . \esc_attr__('opens in a new tab', 'granola') . ')']
            : ['download' => '', 'aria-label' => $label . ' ' . $title],
        'classes' => ['downloads__file-action', 'downloads__file-action--' . $kind],
        'content_filter' => false,
    ];
}

function format_file_size(int $bytes): string
{
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = (int) floor(log($bytes) / log(1024));

    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}
