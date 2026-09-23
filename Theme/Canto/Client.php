<?php

namespace Theme\Canto;

/**
 * Canto DAM — read-only client for the brand assets panel.
 *
 * Canto refuses to be framed (`X-Frame-Options: DENY`, and its CSP carries no
 * `frame-ancestors` directive), so the library is read through the API and
 * rendered in the account's own design instead of embedded.
 *
 * Authentication is the client-credentials grant: the server authenticates as
 * itself, once, and serves whoever is signed into the account area. Nobody
 * needs their own Canto login, which is what the design promises.
 *
 * Configuration, all from wp-config.php and never from the database:
 *
 *   MB_CANTO_TENANT      subdomain, e.g. 'millboard'
 *   MB_CANTO_DOMAIN      'canto.global' | 'canto.com' | 'cantoflight.com'
 *   MB_CANTO_APP_ID      from Settings > Configuration > API Keys
 *   MB_CANTO_APP_SECRET  the same screen
 *
 * The OAuth host has to match the account's domain: a .global account can only
 * authorise against oauth.canto.global.
 */
class Client
{
    private const TOKEN_TRANSIENT = 'mb_canto_token';

    /**
     * Shave a day off whatever expiry Canto reports, so a token is never used
     * in the minutes around its own expiry.
     */
    private const EXPIRY_MARGIN = DAY_IN_SECONDS;

    public static function is_configured(): bool
    {
        foreach (['MB_CANTO_TENANT', 'MB_CANTO_DOMAIN', 'MB_CANTO_APP_ID', 'MB_CANTO_APP_SECRET'] as $constant) {
            if (!\defined($constant) || '' === (string) \constant($constant)) {
                return false;
            }
        }

        return true;
    }

    private static function tenant(): string
    {
        return (string) \constant('MB_CANTO_TENANT');
    }

    private static function domain(): string
    {
        return (string) \constant('MB_CANTO_DOMAIN');
    }

    public static function library_url(): string
    {
        return 'https://' . self::tenant() . '.' . self::domain() . '/';
    }

    /**
     * A bearer token, from cache when there is one.
     *
     * Canto issues these with a 30 day life, so this is cached rather than
     * fetched per request. Returns '' when the exchange fails; every caller
     * treats that as "show the fallback", never as an empty library.
     */
    public static function token(bool $force = false): string
    {
        if (!self::is_configured()) {
            return '';
        }

        if (!$force) {
            $cached = \get_transient(self::TOKEN_TRANSIENT);

            if (\is_string($cached) && '' !== $cached) {
                return $cached;
            }
        }

        $response = \wp_remote_post(
            'https://oauth.' . self::domain() . '/oauth/api/oauth2/token',
            [
                'timeout' => 20,
                'body' => [
                    'app_id' => (string) \constant('MB_CANTO_APP_ID'),
                    'app_secret' => (string) \constant('MB_CANTO_APP_SECRET'),
                    'grant_type' => 'client_credentials',
                    'scope' => 'admin',
                ],
            ]
        );

        if (\is_wp_error($response) || 200 !== \wp_remote_retrieve_response_code($response)) {
            self::log('token exchange failed', $response);
            return '';
        }

        $body = \json_decode((string) \wp_remote_retrieve_body($response), true);
        $token = \is_array($body) ? (string) ($body['accessToken'] ?? '') : '';

        if ('' === $token) {
            return '';
        }

        $expires = (int) ($body['expiresIn'] ?? 0);
        $life = $expires > self::EXPIRY_MARGIN ? $expires - self::EXPIRY_MARGIN : HOUR_IN_SECONDS;

        \set_transient(self::TOKEN_TRANSIENT, $token, $life);

        return $token;
    }

    /**
     * A GET against the tenant's API.
     *
     * A 401 is retried once with a freshly minted token: a revoked or rotated
     * key is the one failure that a cached token causes and that a new one
     * fixes, and it is not worth a support call.
     *
     * @param array<string,scalar> $args
     * @return array<mixed>|null Decoded body, or null on any failure.
     */
    public static function get(string $path, array $args = [], bool $retrying = false): ?array
    {
        $token = self::token($retrying);

        if ('' === $token) {
            return null;
        }

        $url = 'https://' . self::tenant() . '.' . self::domain() . '/api/v1/' . \ltrim($path, '/');

        if ($args) {
            $url = \add_query_arg(\array_map('rawurlencode', $args), $url);
        }

        $response = \wp_remote_get($url, [
            'timeout' => 20,
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        if (\is_wp_error($response)) {
            self::log('request failed: ' . $path, $response);
            return null;
        }

        $code = \wp_remote_retrieve_response_code($response);

        if (401 === $code && !$retrying) {
            \delete_transient(self::TOKEN_TRANSIENT);
            return self::get($path, $args, true);
        }

        if (200 !== $code) {
            self::log('request returned ' . $code . ': ' . $path, $response);
            return null;
        }

        $body = \json_decode((string) \wp_remote_retrieve_body($response), true);

        return \is_array($body) ? $body : null;
    }

    /**
     * The top level of the library: folders and albums, in Canto's own order.
     *
     * Cached for an hour. The library changes rarely and this sits behind a
     * staff-only tab, so a stale hour costs nothing and a per-view round trip
     * would be felt.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function tree(): array
    {
        $cached = \get_transient('mb_canto_tree');

        if (\is_array($cached)) {
            return $cached;
        }

        $body = self::get('tree', ['sortBy' => 'name', 'sortDirection' => 'ascending']);
        $results = \is_array($body) && isset($body['results']) && \is_array($body['results'])
            ? $body['results']
            : [];

        if ($results) {
            \set_transient('mb_canto_tree', $results, HOUR_IN_SECONDS);
        }

        return $results;
    }

    /**
     * Assets inside one album or folder.
     *
     * @return array{results: array<int,array<string,mixed>>, found: int}
     */
    public static function contents(string $scheme, string $id, int $limit = 60, int $start = 0): array
    {
        $scheme = \in_array($scheme, ['album', 'folder'], true) ? $scheme : 'album';

        $body = self::get($scheme . '/' . $id, [
            'limit' => $limit,
            'start' => $start,
            'sortBy' => 'name',
            'sortDirection' => 'ascending',
        ]);

        return [
            'results' => \is_array($body) && \is_array($body['results'] ?? null) ? $body['results'] : [],
            'found' => \is_array($body) ? (int) ($body['found'] ?? 0) : 0,
        ];
    }

    /**
     * Search the whole library.
     *
     * @return array{results: array<int,array<string,mixed>>, found: int}
     */
    public static function search(string $keyword, int $limit = 60, int $start = 0): array
    {
        $keyword = \trim($keyword);

        if ('' === $keyword) {
            return ['results' => [], 'found' => 0];
        }

        $body = self::get('search', [
            'keyword' => $keyword,
            'limit' => $limit,
            'start' => $start,
        ]);

        return [
            'results' => \is_array($body) && \is_array($body['results'] ?? null) ? $body['results'] : [],
            'found' => \is_array($body) ? (int) ($body['found'] ?? 0) : 0,
        ];
    }

    /**
     * Drop everything cached. Used by the panel's own refresh control.
     */
    public static function flush(): void
    {
        \delete_transient(self::TOKEN_TRANSIENT);
        \delete_transient('mb_canto_tree');
    }

    /**
     * @param \WP_Error|array<mixed>|null $response
     */
    private static function log(string $message, $response = null): void
    {
        if (!\defined('WP_DEBUG') || !\WP_DEBUG) {
            return;
        }

        $detail = '';

        if (\is_wp_error($response)) {
            $detail = ' ' . $response->get_error_message();
        } elseif (\is_array($response)) {
            // Body only, and truncated: a Canto error can echo the request back.
            $detail = ' ' . \substr((string) \wp_remote_retrieve_body($response), 0, 200);
        }

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        \error_log('[canto] ' . $message . $detail);
    }
}
