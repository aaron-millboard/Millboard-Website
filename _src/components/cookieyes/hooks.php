<?php

namespace Granola\Components\CookieYes;

\add_action('wp_enqueue_scripts', function () {

    $enabled = \get_field('cookieyes_enable', 'options');

    if (!$enabled) {
        return;
    }

    $url = \get_field('cookieyes_url', 'options');

    if (empty($url)) {
        return;
    }

    \wp_enqueue_script('cookieyes', $url, [], null, false);
});
