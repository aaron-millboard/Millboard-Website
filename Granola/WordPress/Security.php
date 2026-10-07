<?php

namespace Granola\WordPress;

/**
 * Adds, removes, or changes WP functionality to improve site security.
 */
class Security
{
    public static function init(): void
    {
        // Hide current WP version globally to give less information to attackers.
        \add_action('init', [__CLASS__, 'hide_wp_version']);

        // Filter login errors to give less information to attackers.
        \add_filter('login_errors', [__CLASS__, 'filter_login_error_messages']);

        // Hide 'Users' REST API endpoint from non-authenticated users.
        \add_filter('rest_request_before_callbacks', [__CLASS__, 'filter_rest_api_users_endpoint'], 10, 3);

        // oEmbed responses otherwise publish the author name and archive URL (which carries the username).
        \add_filter('oembed_response_data', [__CLASS__, 'filter_oembed_author']);

        // Known and unknown accounts must get the same answer from both password reset forms.
        \add_action('login_form_lostpassword', [__CLASS__, 'uniform_lost_password'], 1);
        \add_action('wp_loaded', [__CLASS__, 'uniform_woocommerce_lost_password'], 21);

        // Browser security headers.
        \add_action('send_headers', [__CLASS__, 'send_hsts_header']);
        \add_action('login_init', [__CLASS__, 'send_hsts_header']);
        \add_action('send_headers', [__CLASS__, 'send_content_security_policy']);

        // Cross-origin REST requests are only trusted from this site's own origins.
        \remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
        \add_filter('rest_pre_serve_request', [__CLASS__, 'send_rest_cors_headers']);

        self::lock_down_wp_cron();
    }

    /**
     * Filter generated WP version output from various feeds and locations.
     *
     * @see /wp-includes/default-filters.php
     * @see /wp-includes/general-template.php
     *
     * @return void
     */
    public static function hide_wp_version(): void
    {
        \add_filter('the_generator', '__return_empty_string');
    }

    /**
     * Filter login error messages so that username enumeration cannot happen via the login form.
     *
     * @see /wp-login.php
     *
     * @return string The filtered error message.
     */
    public static function filter_login_error_messages(): string
    {
        return \__('Your username or password is incorrect', 'granola');
    }

    /**
     * Filters the REST API response before executing any callbacks to hide the 'Users' endpoint from
     * non-authenticated users.
     *
     * Note that this filter will not be called for requests that fail to authenticate or
     * fail to match a registered route.
     *
     * @link: https://developer.wordpress.org/reference/hooks/rest_request_before_callbacks/
     *
     * @param \WP_REST_Response|\WP_HTTP_Response|\WP_Error|mixed $response Result to send to the client.
     * @param array $handler Route handler used for the request.
     * @param \WP_REST_Request $request Request used to generate the response.
     * @return \WP_REST_Response|\WP_HTTP_Response|\WP_Error|mixed The filtered request used to generate the response.
     */
    public static function filter_rest_api_users_endpoint($response, $handler, $request)
    {
        // Disallowed routes: the list, and every single-user route except the visitor's own (/users/me).
        $route = $request->get_route();
        $is_users_route = $route === '/wp/v2/users' || preg_match('#^/wp/v2/users/(?!me/?$)#', $route) === 1;

        // Check for allowed capability and allowed route(s).
        if (!\current_user_can('edit_posts') && $is_users_route) {
            return new \WP_Error(
                'forbidden',
                \__('Access forbidden.', 'granola'),
                [
                    'status' => 403
                ]
            );
        }

        return $response;
    }

    /**
     * Remove the author name and archive URL from oEmbed responses.
     *
     * @param array $data The oEmbed response data.
     * @return array
     */
    public static function filter_oembed_author(array $data): array
    {
        unset($data['author_name'], $data['author_url']);

        return $data;
    }

    /**
     * Send every wp-login.php password reset to the same "check your email" screen.
     *
     * WordPress answers an unknown account with an error and a known one with a redirect, which lets
     * anyone test whether a username exists. retrieve_password() still emails a genuine account.
     *
     * @return void
     */
    public static function uniform_lost_password(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return;
        }

        \retrieve_password();

        \wp_safe_redirect(\add_query_arg('checkemail', 'confirm', \wp_login_url()));
        exit;
    }

    /**
     * The same, for the WooCommerce My Account "lost password" form.
     *
     * Runs after WooCommerce has handled the form. A known account has already redirected, so
     * reaching here with an error means the account was not found: send it the same way.
     *
     * @return void
     */
    public static function uniform_woocommerce_lost_password(): void
    {
        if (
            !isset($_POST['wc_reset_password'], $_POST['user_login'])
            || trim((string) \wp_unslash($_POST['user_login'])) === ''
            || !\function_exists('wc_notice_count')
            || \wc_notice_count('error') === 0
        ) {
            return;
        }

        \wc_clear_notices();
        \wp_safe_redirect(\add_query_arg('reset-link-sent', 'true', \wc_get_account_endpoint_url('lost-password')));
        exit;
    }

    /**
     * Tell browsers to use HTTPS for this host for a year. Sub-domains are left out on purpose.
     *
     * @return void
     */
    public static function send_hsts_header(): void
    {
        if (!\headers_sent() && \is_ssl()) {
            \header('Strict-Transport-Security: max-age=31536000');
        }
    }

    /**
     * Content Security Policy.
     *
     * Enforced: no script from data: or plain http:, no plugins, no <base> hijack, framing only by this
     * site (and Convert, whose visual editor frames the site).
     * Report-only: forms may only submit to this site. Watch the browser console for violations
     * before moving it to the enforced policy, because third-party forms and payment hand-offs could trip it.
     *
     * @return void
     */
    public static function send_content_security_policy(): void
    {
        if (\headers_sent()) {
            return;
        }

        \header(
            "Content-Security-Policy: script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: https:; "
            . "object-src 'none'; base-uri 'self'; frame-ancestors 'self' https://*.convert.com"
        );
        \header("Content-Security-Policy-Report-Only: form-action 'self'");
    }

    /**
     * CORS headers for REST responses, replacing the WordPress default of echoing back any origin
     * with credentials allowed.
     *
     * @param mixed $served Whether the request has already been served.
     * @return mixed
     */
    public static function send_rest_cors_headers($served)
    {
        $origin = \get_http_origin();

        if ($origin && in_array(rtrim($origin, '/'), self::own_origins(), true)) {
            \header('Access-Control-Allow-Origin: ' . \esc_url_raw($origin));
            \header('Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE');
            \header('Access-Control-Allow-Credentials: true');
            \header('Vary: Origin', false);
        }

        return $served;
    }

    /**
     * This site's own origins: the home URL host with and without www.
     *
     * @return string[]
     */
    private static function own_origins(): array
    {
        $scheme = \wp_parse_url(\home_url(), PHP_URL_SCHEME);
        $host = (string) \wp_parse_url(\home_url(), PHP_URL_HOST);
        $bare = preg_replace('/^www\./', '', $host);

        return array_values(array_unique(["$scheme://$host", "$scheme://$bare", "$scheme://www.$bare"]));
    }

    /**
     * Stop visitors triggering wp-cron.php. The server cron calls it from localhost, so
     * WordPress does not also need to spawn it on page loads.
     *
     * @return void
     */
    private static function lock_down_wp_cron(): void
    {
        if (!\defined('DISABLE_WP_CRON')) {
            \define('DISABLE_WP_CRON', true);
        }

        if (
            PHP_SAPI !== 'cli'
            && \defined('DOING_CRON')
            && basename($_SERVER['SCRIPT_NAME'] ?? '') === 'wp-cron.php'
            && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
        ) {
            \status_header(403);
            exit;
        }
    }
}
