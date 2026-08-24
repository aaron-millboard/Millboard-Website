<?php

namespace Theme\Hubspot;

/**
 * Stops any environment that is not production writing to the live HubSpot portal,
 * and records what it would have sent.
 *
 * Staging is a clone of production, so it inherits production's CRM Perks feeds
 * pointing at the live portal, published and firing on order creation. A completed
 * test order on staging therefore creates real contacts and real orders in portal
 * 26853518 and can change a real person's marketing preferences. Drafting the feeds
 * by hand is not protection, because feed status is a row in the database and the
 * next live to staging clone re-publishes them with nobody noticing. Same class of
 * problem as the Perfmatters exclusions and the rewrite rules: per-environment
 * database state that a clone silently resets.
 *
 * This lives in the theme instead, so it is version controlled, ships to every
 * environment, and works out where it is from the site host rather than from any
 * configuration somebody has to remember to set.
 *
 * Reads are deliberately left alone. Only write methods are blocked, so HubSpot
 * admin screens and CRM Perks' own property catalogue refresh keep working on
 * staging. What gets blocked is only ever the creation or modification of records.
 *
 * It doubles as the test instrument. CRM Perks calls its logger unconditionally
 * after attempting a push, so a blocked send still writes a full row to
 * `wp_<blog>_vxc_hubspot_log` with the mapped payload in `data`. Its own request
 * wrapper handles a WP_Error cleanly rather than fataling. So on staging you get a
 * complete receipt of what the feed built, from the real feed configuration, with
 * nothing leaving the building. The blocked body is logged here as well, which is
 * the literal wire payload rather than the plugin's rendering of it.
 *
 * To deliberately allow writes from a non-production environment, define
 * MILLBOARD_ALLOW_CRM_WRITES as true in wp-config.php. Undefine it afterwards.
 */
class WriteGuard
{
    /**
     * Hosts that are the real site. Everything else is staging, local, or a clone.
     */
    private const PRODUCTION_HOSTS = [
        'www.millboard.com',
        'millboard.com',
    ];

    /**
     * HubSpot API hosts. Covers CRM Perks, the Gravity Forms HubSpot add-on, and
     * the theme's own attribution calls in one place.
     */
    private const GUARDED_HOSTS = [
        'api.hubapi.com',
        'api.hsforms.com',
    ];

    private const WRITE_METHODS = [
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
    ];

    /**
     * Paths that use a write method but do not write a record, so blocking them
     * breaks the integration without protecting anything.
     *
     * `oauth/v1/token` is the token exchange and refresh. Block it and the stored
     * access token expires with no way to renew it, the plugin's connection shows
     * as failed, and the feed screen cannot load its property list. HubSpot refresh
     * tokens are reusable rather than single-use, so a refresh here does not
     * invalidate production's. If production's connection ever drops right after a
     * reconnect elsewhere, that assumption is the thing to re-check.
     *
     * Note what is deliberately NOT here: `oauth/v1/refresh-tokens/...`, which the
     * plugin calls with DELETE to disconnect. Staging shares production's refresh
     * token because it is a clone, so disconnecting from staging would revoke
     * production's access. That one stays blocked.
     */
    private const PERMITTED_WRITE_PATHS = [
        '/oauth/v1/token',
    ];

    /**
     * HubSpot expresses queries as POST. They read, they do not write, and the
     * plugin relies on them to decide whether a record already exists, so blocking
     * them would make staging behave differently from production for no benefit.
     */
    private const PERMITTED_WRITE_PATH_SUFFIXES = [
        '/search',
    ];

    /**
     * Cap on how much of a blocked body gets logged, so a large payload cannot fill
     * the log file.
     */
    private const MAX_LOGGED_BODY = 20000;

    public static function init(): void
    {
        if (self::is_production()) {
            return;
        }

        \add_filter('pre_http_request', [__CLASS__, 'maybe_block'], 10, 3);
    }

    /**
     * @param false|array<string, mixed>|\WP_Error $preempt
     * @param array<string, mixed> $args
     * @return false|array<string, mixed>|\WP_Error
     */
    public static function maybe_block($preempt, $args, $url)
    {
        // Somebody else already short-circuited this request. Leave it alone.
        if ($preempt !== false) {
            return $preempt;
        }

        if (\defined('MILLBOARD_ALLOW_CRM_WRITES') && \MILLBOARD_ALLOW_CRM_WRITES) {
            return $preempt;
        }

        if (!is_string($url) || !is_array($args)) {
            return $preempt;
        }

        $host = strtolower((string) \wp_parse_url($url, PHP_URL_HOST));

        if (!in_array($host, self::GUARDED_HOSTS, true)) {
            return $preempt;
        }

        $method = strtoupper((string) ($args['method'] ?? 'GET'));

        if (!in_array($method, self::WRITE_METHODS, true)) {
            return $preempt;
        }

        if (self::is_permitted_write((string) \wp_parse_url($url, PHP_URL_PATH))) {
            return $preempt;
        }

        self::log_blocked($method, $url, $args);

        return new \WP_Error(
            'millboard_crm_write_blocked',
            sprintf(
                'Blocked %1$s to %2$s: this is not production (%3$s). Define '
                    . 'MILLBOARD_ALLOW_CRM_WRITES in wp-config.php to allow it.',
                $method,
                $host,
                self::current_host()
            )
        );
    }

    /**
     * Authentication and queries pass, record writes do not.
     */
    private static function is_permitted_write(string $path): bool
    {
        $path = rtrim(strtolower($path), '/');

        if ($path === '') {
            return false;
        }

        foreach (self::PERMITTED_WRITE_PATHS as $permitted) {
            if ($path === $permitted) {
                return true;
            }
        }

        foreach (self::PERMITTED_WRITE_PATH_SUFFIXES as $suffix) {
            if (str_ends_with($path, $suffix)) {
                return true;
            }
        }

        return false;
    }

    private static function is_production(): bool
    {
        return in_array(self::current_host(), self::PRODUCTION_HOSTS, true);
    }

    private static function current_host(): string
    {
        // network_site_url() rather than home_url(), so the answer does not depend on
        // which subsite happens to be in scope when this runs.
        return strtolower((string) \wp_parse_url(\network_site_url(), PHP_URL_HOST));
    }

    /**
     * Record the payload that did not go out.
     *
     * Bodies can contain customer details. This only ever runs off production, and
     * only for a request that was blocked, which is the entire point: on staging the
     * log is how you check what a feed built. Do not repurpose it for production.
     *
     * @param array<string, mixed> $args
     */
    private static function log_blocked(string $method, string $url, array $args): void
    {
        $body = $args['body'] ?? '';

        if (is_array($body)) {
            $body = (string) \wp_json_encode($body);
        }

        $body = (string) $body;

        if (strlen($body) > self::MAX_LOGGED_BODY) {
            $body = substr($body, 0, self::MAX_LOGGED_BODY) . '... [truncated]';
        }

        $message = sprintf('BLOCKED %1$s %2$s | body: %3$s', $method, $url, $body);

        if (function_exists('wc_get_logger')) {
            \wc_get_logger()->warning($message, ['source' => 'millboard-crm-write-guard']);

            return;
        }

        \error_log('[millboard-crm-write-guard] ' . $message);
    }
}
