<?php

namespace Granola\Components\GTM;

function render_gtm_noscript($gtm_id)
{
    if (empty($gtm_id)) {
        return;
    }

    ob_start(); ?>

    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= esc_js($gtm_id); ?>"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <?php

    return ob_get_clean();
}
