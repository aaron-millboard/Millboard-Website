<?php

namespace Granola\Components\InstallerVideo;

function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'eyebrow' => '',
        'heading' => '',
        'intro' => '',
        'cover_image' => null,
        'video_url' => '',
        'duration' => '',
    ], $args);

    $args['classes'] = array_merge([
        'installer-video',
        'wp-block',
    ], $args['classes']);

    // Bail early - return null for no output (unless previewing in the editor).
    if (empty($args['heading']) && empty($args['cover_image']) && empty($args['video_url']) && empty($args['is_preview'])) {
        return null;
    }

    return $args;
}
