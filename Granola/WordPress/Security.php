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
        // Disallowed routes.
        $routes = [
            '/wp/v2/users',
        ];

        // Check for allowed capability and allowed route(s).
        if (!\current_user_can('edit_posts') && in_array($request->get_route(), $routes, true)) {
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
}
