<?php

namespace Theme\Utils;

/**
 * Which environment is this, decided from the site host.
 *
 * Deliberately not `wp_get_environment_type()`. That reads WP_ENVIRONMENT_TYPE, which on
 * this install returns "production" on the Kinsta staging box - measured 3 Sep 2026. Any
 * guard trusting it would switch itself off exactly where it is needed. Fixing the constant
 * is worth doing separately, but no safety check should depend on a value that has already
 * been wrong once.
 *
 * `network_site_url()` rather than `home_url()`, so the answer does not change depending on
 * which subsite happens to be in scope when it runs.
 *
 * NOTE: Theme\Hubspot\WriteGuard predates this helper and carries its own private copy of
 * the same host list and check. It is intentionally left alone - it is the only thing
 * standing between staging and the live HubSpot portal, and refactoring it is not worth the
 * risk in passing. Migrate it to this helper next time that file is opened for other
 * reasons, and re-run the functional probe afterwards rather than trusting the diff.
 */
class Environment
{
    /**
     * Hosts that are the real site. Everything else is staging, local, or a clone.
     *
     * Must stay in step with WriteGuard::PRODUCTION_HOSTS until that class is migrated.
     */
    private const PRODUCTION_HOSTS = [
        'www.millboard.com',
        'millboard.com',
    ];

    public static function is_production(): bool
    {
        return in_array(self::current_host(), self::PRODUCTION_HOSTS, true);
    }

    public static function current_host(): string
    {
        return strtolower((string) \wp_parse_url(\network_site_url(), PHP_URL_HOST));
    }
}
