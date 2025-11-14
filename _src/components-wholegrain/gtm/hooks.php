<?php

namespace Granola\Components\GTM;

// Output the GTM code that appears in the <head>.
\add_action('wp_head', function (): void {
    echo render_gtm_script(\get_field('gtm_id', 'option'));
});


// Output the GTM code that appears at the start of the <body>.
\add_action('wp_body_open', function (): void {
    echo render_gtm_noscript(\get_field('gtm_id', 'option'));
});
