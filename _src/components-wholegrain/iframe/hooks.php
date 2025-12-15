<?php

namespace Granola\Components\Iframe;

\add_filter('granola/partial/assets/components/iframe', __NAMESPACE__ . '\\filter_args');

\add_action('wp_enqueue_scripts', function () {
    $url = 'https://widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js';
    \wp_enqueue_script('trustpilot', $url, [], null, false);
});
