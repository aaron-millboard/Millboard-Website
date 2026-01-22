<?php

namespace Granola\Components\Admin;

/**
 * Enqueue custom CSS for the WP admin bar on the front- and back-end.
 */
function enqueue_admin_bar_styles()
{
    if (!\is_admin_bar_showing()) {
        return;
    }

    \wp_enqueue_style(
        'granola-admin-bar-styles',
        \Granola\Asset::URL('components/admin/admin-bar.css', true),
        [],
        false
    );
}
