<?php

namespace Granola\Components\Multisite;

function copy_sunrise_to_wp_content_directory(): void
{
    // Bail early - don't override sunrise.php.
    if (file_exists(\WP_CONTENT_DIR . '/sunrise.php')) {
        return;
    }

    $source = \Granola\Asset::path('components/multisite/sunrise.php', true);
    $dest = \WP_CONTENT_DIR . '/sunrise.php';

    copy($source, $dest);
}
