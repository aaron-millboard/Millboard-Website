<?php

namespace Granola\Components\CookieYes;

 \add_action('wp_enqueue_scripts', function () {

    if (\wp_get_environment_type() === 'production') {
        $url = 'https://cdn-cookieyes.com/client_data/fba0fc734e7279f30fd67a366086c874/script.js';
    } else {
        $url = 'https://cdn-cookieyes.com/client_data/738b450b7bbb82d6dda3f02a3a11b2ca/script.js';
    }

    \wp_enqueue_script('cookieyes', $url, [], null, false);
 });
