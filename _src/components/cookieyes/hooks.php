<?php

namespace Granola\Components\CookieYes;

 \add_action('wp_enqueue_scripts', function () {
    \wp_enqueue_script('cookieyes', 'https://cdn-cookieyes.com/client_data/fba0fc734e7279f30fd67a366086c874/script.js', [], null, false);
 });
