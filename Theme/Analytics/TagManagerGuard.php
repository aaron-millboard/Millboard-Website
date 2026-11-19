<?php

namespace Theme\Analytics;

use Theme\Utils\Environment;

/**
 * Stops non-production environments loading the live Google Tag Manager container.
 *
 * The GTM component only bails on `Granola\Helpers::is_local_environment()`, so staging
 * outputs the PRODUCTION container. Measured on the Kinsta staging box, 3 Sep 2026:
 * `gtm_id` is `GTM-TBPW5F2` on every locale, and `wp_get_environment_type()` reports
 * "production" there, so nothing else was going to catch it either.
 *
 * The consequence is that a staging test order fires real conversions into live accounts:
 * a Google Ads conversion on tag 430, which carries `consentStatus: notNeeded` and so does
 * not even need consent to fire; a GA4 purchase into property 313291284; and a Meta event
 * once consent is accepted. Every test order anyone has placed on staging has been feeding
 * the same conversion data the agency bids on. It is the same class of problem as the
 * HubSpot write guard - a clone inheriting production's configuration - and it deserves the
 * same treatment, in code rather than in a setting somebody has to remember.
 *
 * Filters the component's args to null rather than editing the component itself, so the
 * agency's `_src/components-wholegrain/gtm/*` files stay untouched and a future update to
 * them cannot quietly drop the guard.
 *
 * DELIBERATE TRADE-OFF: suppressing GTM on staging also means the ad and analytics cookies
 * (`_fbp`, `_ga`, `_gcl_aw`) are never set there, so anything that reads them has nothing to
 * read. That is the right default - polluting live conversion data is far more expensive
 * than fabricating a cookie - and OrderIdentity is better tested with cookies set by hand
 * anyway, because that can cover the absent and consent-denied cases that a live container
 * would not reliably reproduce.
 *
 * To QA tags on staging with GTM Preview, define MILLBOARD_ALLOW_TAG_MANAGER as true in
 * wp-config.php, do the work, then undefine it. Mirrors MILLBOARD_ALLOW_CRM_WRITES.
 *
 * STAGING ENVIRONMENT (test rig, GA4 audit fixes, Nov 2026). If wp-config.php defines
 * MILLBOARD_GTM_ENV_PREVIEW (e.g. 'env-531') and MILLBOARD_GTM_ENV_AUTH (the environment's
 * authorization code), the component's snippet is suppressed and this class prints its own
 * snippet with `gtm_auth`, `gtm_preview` and `gtm_cookies_win=x` added, so staging loads the
 * container's "Staging" environment (a separate container version whose GA4 tags send to
 * the GA4 TEST property and whose ad tags are paused) instead of the Live version. The
 * agency component cannot carry these parameters: it prints `gtm_id` through esc_js, which
 * turns `&` into `&amp;`. The auth code lives in wp-config.php, never in the repository.
 * The environment wins over MILLBOARD_ALLOW_TAG_MANAGER, so the live version can never load
 * on a site where both are set. Nothing here runs on production (init() returns first).
 */
class TagManagerGuard
{
    /**
     * The component filters that decide whether GTM renders.
     *
     * Both halves are guarded. The <head> half loads the container; the <body> half is the
     * noscript iframe. Leaving the noscript behind would still register a hit.
     */
    private const COMPONENT_FILTERS = [
        'granola/component/gtm/head',
        'granola/component/gtm/body',
    ];

    public static function init(): void
    {
        if (Environment::is_production()) {
            return;
        }

        // Priority 20, so this runs after the component's own filter_args at the default 10
        // and has the final say on whether anything renders.
        foreach (self::COMPONENT_FILTERS as $filter) {
            \add_filter($filter, [__CLASS__, 'maybe_suppress'], 20);
        }

        // Leave a trace in the markup. Without one, the only symptom is that tags silently
        // do not fire, which reads exactly like a broken container and has cost this project
        // days before.
        \add_action('wp_head', [__CLASS__, 'render_notice'], 99);

        if (self::environment_query() !== '') {
            // Same positions as the component: priority 100 in <head>, wp_body_open for noscript.
            \add_action('wp_head', [__CLASS__, 'render_head'], 100);
            \add_action('wp_body_open', [__CLASS__, 'render_body']);
        }
    }

    /**
     * Query string that selects the GTM environment, or '' when none is configured.
     * Built with rawurlencode and joined by a literal '&', which is what GTM's own snippet does.
     */
    public static function environment_query(): string
    {
        $preview = \defined('MILLBOARD_GTM_ENV_PREVIEW') ? (string) \MILLBOARD_GTM_ENV_PREVIEW : '';
        $auth = \defined('MILLBOARD_GTM_ENV_AUTH') ? (string) \MILLBOARD_GTM_ENV_AUTH : '';

        if ($preview === '' || $auth === '') {
            return '';
        }

        return '&gtm_auth=' . rawurlencode($auth) . '&gtm_preview=' . rawurlencode($preview) . '&gtm_cookies_win=x';
    }

    public static function render_head(): void
    {
        $id = (string) \get_field('gtm_id', 'option');
        if ($id === '') {
            return;
        }

        $json = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        echo "<!-- Google Tag Manager (staging environment) -->\n";
        echo "<script>(function(w,d,s,l,i,q){w[l]=w[l]||[];w[l].push({'gtm.start':\n"
            . "new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],\n"
            . "j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n"
            . "'https://www.googletagmanager.com/gtm.js?id='+i+dl+q;f.parentNode.insertBefore(j,f);\n"
            . "})(window,document,'script','dataLayer',"
            . \wp_json_encode($id, $json) . ',' . \wp_json_encode(self::environment_query(), $json)
            . ");</script>\n<!-- End Google Tag Manager (staging environment) -->\n";
    }

    public static function render_body(): void
    {
        $id = (string) \get_field('gtm_id', 'option');
        if ($id === '') {
            return;
        }

        $src = 'https://www.googletagmanager.com/ns.html?id=' . rawurlencode($id) . self::environment_query();
        printf(
            "<!-- Google Tag Manager (noscript, staging environment) -->\n" .
            "<noscript><iframe src=\"%s\" height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>\n" .
            "<!-- End Google Tag Manager (noscript, staging environment) -->\n",
            \esc_url($src)
        );
    }

    /**
     * @param mixed $args
     * @return mixed Null suppresses the component.
     */
    public static function maybe_suppress($args)
    {
        if (self::explicitly_allowed()) {
            return $args;
        }

        return null;
    }

    public static function render_notice(): void
    {
        if (self::explicitly_allowed()) {
            return;
        }

        if (self::environment_query() !== '') {
            echo "<!-- Google Tag Manager: live container suppressed, loading the Staging environment instead. See Theme\\Analytics\\TagManagerGuard. -->\n";
            return;
        }

        printf(
            "<!-- Google Tag Manager suppressed: %s is not production. " .
            "Define MILLBOARD_ALLOW_TAG_MANAGER to enable for tag QA. " .
            "See Theme\\Analytics\\TagManagerGuard. -->\n",
            \esc_html(Environment::current_host())
        );
    }

    private static function explicitly_allowed(): bool
    {
        // A configured environment always wins: the live version must never load alongside it.
        return self::environment_query() === ''
            && \defined('MILLBOARD_ALLOW_TAG_MANAGER') && \MILLBOARD_ALLOW_TAG_MANAGER;
    }
}
