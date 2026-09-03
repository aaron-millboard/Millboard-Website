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
 * The longer-term answer is a separate GTM container for non-production, which would let
 * tags be QA'd on staging without touching live data at all. This is the stopgap that makes
 * staging safe today.
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

        printf(
            "<!-- Google Tag Manager suppressed: %s is not production. " .
            "Define MILLBOARD_ALLOW_TAG_MANAGER to enable for tag QA. " .
            "See Theme\\Analytics\\TagManagerGuard. -->\n",
            \esc_html(Environment::current_host())
        );
    }

    private static function explicitly_allowed(): bool
    {
        return \defined('MILLBOARD_ALLOW_TAG_MANAGER') && \MILLBOARD_ALLOW_TAG_MANAGER;
    }
}
