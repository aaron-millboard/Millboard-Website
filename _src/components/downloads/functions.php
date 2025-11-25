<?php

namespace Granola\Components\Downloads;

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
        'background' => 'none',
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'downloads',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Bail early if no content
    // -------------------------------------------------------------------------
    if (empty($args['files'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Background color
    // -------------------------------------------------------------------------
    if (!empty($args['background']) && $args['background'] !== 'none') {
        $args['classes'][] = 'has-' . $args['background'] . '-background-color';
        $args['classes'][] = 'has-background';
    }

    // -------------------------------------------------------------------------
    // Process CTA
    // -------------------------------------------------------------------------
    if (!empty($args['cta'])) {
        $args['cta'] = [
            'title'    => $args['cta']['title'] ?? '',
            'url'      => $args['cta']['url'] ?? '',
            'attributes' => [
                'target' => $args['cta']['target'] ?? '',
                'rel'    => $args['cta']['rel'] ?? '',
            ],
            'classes' => [
                'page-header__cta',
                'g-button'
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Process files items
    // -------------------------------------------------------------------------
    if (!empty($args['files']) && is_array($args['files'])) {
        foreach ($args['files'] as $key => $file) {
            if (empty($file['file'])) {
                continue;
            }

            $file_id = $file['file'];
            $file_url = wp_get_attachment_url($file_id);
            $file_path = get_attached_file($file_id);
            $file_size = file_exists($file_path) ? filesize($file_path) : 0;
            $file_label = $file['label'] ?? 'File';

            $args['files'][$key]['url'] = $file_url;
            $args['files'][$key]['size'] = format_file_size($file_size);
            $args['files'][$key]['size_raw'] = $file_size;


            // Prepare VIEW button args
            $args['files'][$key]['actions'][] = [
                'content' => '<span>View</span>',
                'icon' => \Granola\SVG::get('icons-custom/view.svg'),
                'url' => $file_url,
                'attributes' => [
                    'target' => '_blank',
                    'rel' => 'noopener noreferrer',
                    'aria-label' => 'View ' . $file_label,
                ],
                'classes' => [
                    'downloads__file-action',
                    'downloads__file-action--view',
                ],
                'content_filter' => false,
            ];

            // Prepare download button args
            $args['files'][$key]['actions'][] = [
                'content' => '<span>Download</span>',
                'icon' => \Granola\SVG::get('icons-custom/download.svg'),
                'url' => $file_url,
                'attributes' => [
                    'download' => '',
                    'aria-label' => 'Download ' . $file_label,
                ],
                'classes' => [
                    'downloads__file-action',
                    'downloads__file-action--download',
                ],
                'content_filter' => false,
            ];

            // Prepare SHARE button args (this will be a button element, not a link)
            $args['files'][$key]['actions'][] = [
                'el' => 'button',
                'content' => '<span>Share</span>',
                'icon' => \Granola\SVG::get('icons-custom/share.svg'),
                'attributes' => [
                    'data-action' => 'share',
                    'data-url' => $file_url,
                    'data-title' => $file_label,
                    'aria-label' => 'Share ' . $file_label,
                ],
                'classes' => [
                    'downloads__file-action',
                    'downloads__file-action--share',
                ],
                'content_filter' => false,
            ];
        }
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Format file size.
 *
 * @param int $bytes File size in bytes.
 * @return string Formatted file size.
 */
function format_file_size(int $bytes): string
{
    if ($bytes === 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log(1024));

    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}
