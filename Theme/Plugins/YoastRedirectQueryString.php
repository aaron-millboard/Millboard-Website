<?php

namespace Theme\Plugins;

/**
 * Makes Yoast Premium plain redirects fire when the URL carries a query string.
 *
 * Yoast Premium's redirect handler (WPSEO_Redirect_Handler) looks the request up
 * by its exact, slash-trimmed REQUEST_URI, and that string still includes the
 * query string: get_request_uri() does not strip it, normalize_url() only trims
 * slashes, and find_url()/find_url_fallback() do an exact array-key match against
 * origins that are stored without a query. So a plain redirect for
 * "/old-path" fires for "/old-path" but NOT for "/old-path?utm_source=x": the
 * lookup key never matches, no redirect happens, and the request falls through to
 * a 404.
 *
 * Every plain redirect on the site is affected. That matters because the query
 * strings real visitors arrive with are exactly the ones appended automatically:
 * Google Ads (gclid / gbraid), the GA cross-domain linker (_gl), UTM campaign
 * tags and email-platform tracking. An ad click or a shared link that lands on
 * any moved URL (old product-collection URLs now under /shop/, find-a-dealer,
 * etc.) hits a 404.
 *
 * This catches a request that is already heading to a 404 and carries a query
 * string, looks the bare path up in Yoast's own redirect table, and reissues the
 * redirect with the original query string re-appended to the target. It only acts
 * on is_404() AND a non-empty query string, so clean URLs (which Yoast still
 * handles) and genuine 404s are left completely untouched.
 *
 * This is a workaround for the plugin's behaviour, not a theme feature. If Yoast
 * changes the handler to match on the path without the query string, delete this
 * class and its init() call in functions.php.
 */
class YoastRedirectQueryString
{
    public static function init(): void
    {
        \add_action('template_redirect', [__CLASS__, 'maybe_redirect'], 0);
    }

    /**
     * Re-run Yoast's redirect lookup for a would-be 404 that carries a query string.
     *
     * @return void
     */
    public static function maybe_redirect(): void
    {
        if (\is_admin() || \wp_doing_ajax() || (\defined('REST_REQUEST') && \REST_REQUEST)) {
            return;
        }

        if (!\is_404() || empty($_SERVER['QUERY_STRING']) || empty($_SERVER['REQUEST_URI'])) {
            return;
        }

        if (!\class_exists('WPSEO_Redirect_Manager')) {
            return;
        }

        // Bare request path (no query), with the multisite subdirectory prefix
        // (e.g. "/en-gb/") stripped and slashes trimmed, to match how Yoast stores
        // redirect origins.
        $request_path = \strtok(\rawurldecode(\wp_unslash($_SERVER['REQUEST_URI'])), '?');
        $home_path    = \wp_parse_url(\home_url('/'), \PHP_URL_PATH);

        if ($home_path && $home_path !== '/' && \strpos($request_path, $home_path) === 0) {
            $request_path = \substr($request_path, \strlen($home_path));
        }

        $origin = \trim($request_path, '/');

        if ($origin === '') {
            return;
        }

        $manager  = new \WPSEO_Redirect_Manager();
        $redirect = $manager->get_redirect($origin);

        if (!$redirect) {
            return;
        }

        $target = $redirect->get_target();

        if ($target === '' || $target === $origin) {
            return;
        }

        // Yoast stores the target relative to the blog root; build an absolute URL
        // and trailing-slash the path (matching Yoast's own handling) so there is a
        // single clean hop, unless the target looks like a file.
        if (\preg_match('#^https?://#i', $target)) {
            $target_url = $target;
        } else {
            $relative = \ltrim($target, '/');

            if (!\preg_match('#\.[a-z0-9]{2,5}$#i', $relative)) {
                $relative = \trailingslashit($relative);
            }

            $target_url = \home_url('/') . $relative;
        }

        $separator  = (\strpos($target_url, '?') !== false) ? '&' : '?';
        $target_url = $target_url . $separator . $_SERVER['QUERY_STRING'];

        $type = (int) $redirect->get_type();

        if ($type < 300 || $type > 308) {
            $type = 301;
        }

        \wp_safe_redirect($target_url, $type);
        exit;
    }
}
