<?php

namespace Granola\Components\WPConsent;

/**
 * WPConsent's banner X (and Escape) only hides the banner and stores nothing,
 * so it comes back on every page load. Treat a dismissal as saving the current
 * default: everything allowed in opt-out mode, essential only otherwise.
 *
 * savePreferences() sets the cookie before it hides the banner, so a real
 * choice already has a cookie by the time this hook runs and is left alone.
 */
\add_action('wp_enqueue_scripts', function () {

    $handle = 'wpconsent-frontend-js';

    if (!\wp_script_is($handle, 'registered')) {
        return;
    }

    $js = <<<'JS'
(function () {
    var c = window.WPConsent;

    if (!c || !c.addHook) {
        return;
    }

    c.addHook('beforeHideBanner', function () {
        if (c.getCookie('wpconsent_preferences')) {
            return;
        }

        var allow = parseInt(window.wpconsent.default_allow, 10) === 1;
        var prefs = {};

        (window.wpconsent.slugs || []).forEach(function (slug) {
            prefs[slug] = slug === 'essential' || allow;
        });

        c.savePreferences(prefs);
    });
})();
JS;

    \wp_add_inline_script($handle, $js, 'after');
}, 20);
