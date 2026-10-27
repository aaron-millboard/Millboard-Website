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

    private const INDEX_TRANSIENT = 'mb_canto_partner_index';

    private const INDEX_TTL = 45 * MINUTE_IN_SECONDS;

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
     * Is this folder or album restricted in Canto?
     *
     * Canto's own per-container permission, "Restrict to Admin(s) Only" or
     * "Restrict to specific Users & Groups", which the library's listing
     * endpoints do not mention at all. The site reads Canto as one
     * admin-scoped client, so a restricted album comes back like any other and
     * the panel would happily draw it.
     *
     * ⚠️ The flag comes from /rest/{scheme}/{id}, but at a different path for
     * each container type. See the comment at the lookup below.
     * It is NOT on the /api/v1/ listing or in the tree, which is why this needs
     * its own request. An empty `acl` beside the flag means admins only; names
     * appear as `user#…` or `group#…`.
     *
     * Cached per container for an hour, matching the tree, and looked up only
     * for containers actually being listed. A sweep of all 102 containers takes
     * about nine seconds, which is fine as a one-off but not something to hang
     * a page load on; a folder's handful of children costs well under a second
     * once, then nothing.
     *
     * Fails OPEN. If Canto cannot be reached the container is treated as
     * unrestricted, because the alternative is an outage emptying the panel.
     * The asset-level gates in Access still apply either way.
     */
    /**
     * A cover image for a folder or album, as a public URL. '' when there is none.
     *
     * Canto gives folders no cover at all, and an album's own `previewURI` is
     * whatever Canto picked, which may be a PDF, or an asset nobody outside
     * Millboard is allowed to see. So the cover is chosen here instead: the
     * first asset in the container that is an image and reaches external
     * audiences.
     *
     * partner_visible() is the test on purpose, rather than the current
     * viewer's audiences. A cover is decoration shown on a tile before anyone
     * has opened anything, and caching one per audience would triple the
     * lookups to decorate a tile. Requiring the least-privileged audience
     * means the same cover is safe for everyone, staff included, and it
     * carries the usage-rights gate with it.
     *
     * Costs no Canto request: the answer comes from the partner index, which
     * is already fetched and cached for the partner panel. See cover_map().
     */
    public static function cover(string $scheme, string $id): string
    {
        if ('' === $id || !\in_array($scheme, ['folder', 'album'], true)) {
            return '';
        }

        // A deliberate choice wins, but only while it is still a valid one.
        // Covers::resolve_url() handles both libraries and returns '' for a
        // choice that no longer resolves, so a cover that has since been
        // restricted, deleted or had its rights changed quietly reverts to the
        // automatic pick rather than leaving a broken image on the tile.
        $chosen = Covers::for_container($id);

        if ('' !== $chosen) {
            $url = Covers::resolve_url($chosen);

            if ('' !== $url) {
                return $url;
            }
        }

        return self::cover_map()[$id] ?? '';
    }

    /**
     * container id => cover URL, for every container with a usable image.
     *
     * Built from the partner index, which is already fetched and cached for
     * the partner panel, so covers cost no Canto requests at all.
     *
     * Each indexed record carries `albums`: every folder and album id on its
     * idPath, ancestors included. So walking the index once gives a cover for
     * a top-level folder and for the album four levels down from the same
     * pass.
     *
     * The first scanning version asked Canto for each container's first 60
     * assets and picked from those. It found nothing for "01 - Product
     * Assets", because all twelve images in that window are marked Internal
     * staff: the window was wrong, not the rule. The index has no window.
     *
     * Static for the request: the panel asks for a cover per tile, and
     * rebuilding this per tile would be the one expensive thing about a
     * cheap lookup.
     *
     * @return array<string,string>
     */
    /**
     * asset id => public preview URL, for everything in the partner index.
     *
     * Only used to resolve a chosen cover. An id is stored rather than a URL
     * because Canto's direct links carry a signature it can rotate; resolving
     * through the index also means a cover that has since been restricted
     * simply is not found, and the tile falls back to the automatic pick.
     *
     * @return array<string,string>
     */
    public static function asset_url(string $asset_id): string
    {
        return self::asset_map()[$asset_id] ?? '';
    }

    private static function asset_map(): array
    {
        static $map = null;

        if (null !== $map) {
            return $map;
        }

        $map = [];

        foreach ((array) self::partner_index() as $asset) {
            if (!\is_array($asset)) {
                continue;
            }

            $id = (string) ($asset['id'] ?? '');
            $url = (string) ($asset['url']['directUrlPreview'] ?? '');

            if ('' !== $id && '' !== $url) {
                $map[$id] = $url;
            }
        }

        return $map;
    }

    private static function cover_map(): array
    {
        static $map = null;

        if (null !== $map) {
            return $map;
        }

        $map = [];
        $index = self::partner_index();

        foreach ((array) $index as $asset) {
            if (!\is_array($asset) || 'image' !== (string) ($asset['scheme'] ?? '')) {
                continue;
            }

            $url = (string) ($asset['url']['directUrlPreview'] ?? '');

            if ('' === $url) {
                continue;
            }

            // First one wins. The index is sorted by name, so a container's
            // cover is stable between page loads rather than wandering.
            foreach ((array) ($asset['albums'] ?? []) as $container) {
                $container = (string) $container;

                if ('' !== $container && !isset($map[$container])) {
                    $map[$container] = $url;
                }
            }
        }

        return $map;
    }

    public static function restricted(string $scheme, string $id): bool
    {
        if ('' === $id || !\in_array($scheme, ['folder', 'album'], true)) {
            return false;
        }

        $key = 'mb_canto_acl_' . \md5($scheme . '/' . $id);
        $cached = \get_transient($key);

        if (false !== $cached) {
            return '1' === $cached;
        }

        $response = \wp_remote_get(
            'https://' . self::tenant() . '.' . self::domain() . '/rest/' . $scheme . '/' . \rawurlencode($id),
            [
                'timeout' => 15,
                'headers' => [
                    'Authorization' => 'Bearer ' . self::token(),
                    'User-Agent' => 'App Millboard Website',
                ],
            ]
        );

        if (\is_wp_error($response) || 200 !== (int) \wp_remote_retrieve_response_code($response)) {
            self::log('ACL lookup failed for ' . $scheme . '/' . $id, $response);

            return false;
        }

        $body = \json_decode((string) \wp_remote_retrieve_body($response), true);
        /*
         * ⚠️ The flag sits in a DIFFERENT PLACE for the two container types,
         * and reading only the album shape made this silently return false for
         * every folder. An album answers with a record, so it is at
         * data.acl_enabled. A folder answers with a LISTING whose first hit is
         * the folder itself, so it is at hits.hit[0].data.acl_enabled.
         *
         * The symptom was that restricting an album worked and restricting a
         * folder did nothing at all, which reads like a caching problem rather
         * than a missing path.
         */
        $flag = \is_array($body)
            ? ($body['data']['acl_enabled'] ?? ($body['hits']['hit'][0]['data']['acl_enabled'] ?? null))
            : null;

        // Canto answers with ["true"] rather than a boolean, and sometimes a
        // bare string, so compare on the normalised values rather than casting.
        $restricted = \in_array('true', \array_map('strval', (array) $flag), true);

        \set_transient($key, $restricted ? '1' : '0', HOUR_IN_SECONDS);

        return $restricted;
    }

    /**
     * Assets inside one album or folder, limited to what this user may see.
     *
     * `found` is the number of VISIBLE assets reached, never Canto's own total:
     * that total counts the assets this user may not see, so showing it would
     * leak how many there are. `more` says the page filled and Canto has more.
     *
     * `next_start` is an OPAQUE cursor: pass it straight back to get the next
     * page. It is not an item count and the two branches below do not even
     * measure it in the same units (a raw Canto offset for staff, a position
     * in the partner index for everyone else). Nothing outside this class may
     * do arithmetic on it, and a caller must not carry one from `search()`
     * into `contents()` or the other way round.
     *
     * @return array{results: array<int,array<string,mixed>>, found: int, more: bool, next_start: int}
     */
    public static function contents(string $scheme, string $id, int $limit = 60, int $start = 0): array
    {
        $scheme = \in_array($scheme, ['album', 'folder'], true) ? $scheme : 'album';
        $audiences = Access::for_current_user();

        // Partners see a small slice of a large album (about 12% of the library),
        // so walking Canto's own listing page by page is slow. They are answered
        // from the partner index instead, which holds only what they may see.
        if (!\in_array(Access::STAFF, $audiences, true)) {
            if (!$audiences) {
                return ['results' => [], 'found' => 0, 'more' => false, 'next_start' => 0];
            }

            $index = self::partner_index();

            if (null !== $index) {
                $mine = \array_values(\array_filter($index, static fn ($item) => \in_array($id, $item['albums'], true) && Access::visible($item, $audiences)));

                // Already filtered, so here the cursor really is a plain
                // position in $mine -- which is why it must stay opaque to
                // callers that cannot tell which branch answered them.
                return [
                    'results' => \array_slice($mine, $start, $limit),
                    'found' => \count($mine),
                    'more' => \count($mine) > $start + $limit,
                    'next_start' => \min($start + $limit, \count($mine)),
                ];
            }
        }

        return self::visible_page($scheme . '/' . $id, [
            'sortBy' => 'name',
            'sortDirection' => 'ascending',
        ], $limit, $start);
    }

    /**
     * Search the whole library, limited to what this user may see.
     *
     * `next_start` is the same opaque cursor described on contents().
     *
     * @return array{results: array<int,array<string,mixed>>, found: int, more: bool, next_start: int}
     */
    public static function search(string $keyword, int $limit = 60, int $start = 0): array
    {
        $keyword = \trim($keyword);

        if ('' === $keyword) {
            return ['results' => [], 'found' => 0, 'more' => false, 'next_start' => 0];
        }

        /*
         * ⚠️ sortBy is NOT cosmetic. Without it Canto answers a search in an
         * order that is only stable for an IDENTICAL request: ask for the same
         * span with a different page size and the same assets come back in a
         * different order. Measured against the live library: one read of 120
         * from offset 300, versus three reads of 40 covering the same span,
         * returned the same members in a different order every time, and were
         * identical the moment sortBy=name was added.
         *
         * That makes offset paging meaningless, because "asset number 320"
         * depends on how you asked. It also bit a single call, because the walk
         * below deliberately changes page size part way through (100, then
         * 1000), so a search needing more than one request could repeat and
         * drop assets without anyone paging at all.
         *
         * contents() has always sent this, which is why folder listings paged
         * cleanly and search did not.
         */
        return self::visible_page('search', [
            'keyword' => $keyword,
            'sortBy' => 'name',
            'sortDirection' => 'ascending',
        ], $limit, $start);
    }

    /**
     * Page through an endpoint until $limit visible assets are collected.
     *
     * Hidden assets are dropped AFTER the fetch, so a page full of embargoed
     * boards would otherwise come back thin. Raw pages are read until enough
     * visible ones turn up or Canto runs out, capped so one request cannot walk
     * the whole library.
     *
     * `next_start` is the RAW Canto offset to resume from, and it is counted
     * item by item rather than page by page.
     *
     * ⚠ Counting by page is WRONG here, which is what this used to do. A raw
     * page can carry more visible assets than are being returned; the surplus
     * was sliced off the end AFTER the cursor had already moved past the whole
     * page, so resuming from it would step straight over assets nobody had
     * seen. Nothing ever resumed while the panel rendered one fixed page of 60,
     * so the bug was inert. Paging is what makes it live, so it is fixed here
     * rather than worked around in the caller.
     *
     * @param array<string,scalar> $args
     * @return array{results: array<int,array<string,mixed>>, found: int, more: bool, next_start: int}
     */
    private static function visible_page(string $path, array $args, int $limit, int $start): array
    {
        $audiences = Access::for_current_user();
        $limit = \max(1, $limit);
        // The first request is small, which is all a staff view needs because
        // nearly everything is visible to them. When that is not enough the
        // pages grow to 1000, Canto's largest, and eleven requests cover the
        // whole library (about 9,300 assets), so a listing is complete rather
        // than cut off after the first thousand.
        $requests = 11;
        $visible = [];
        $cursor = \max(0, $start);
        $more = false;

        for ($i = 0; $i < $requests; $i++) {
            $step = 0 === $i ? 100 : 1000;
            $body = self::get($path, $args + ['limit' => $step, 'start' => $cursor]);
            $raw = \is_array($body) && \is_array($body['results'] ?? null) ? $body['results'] : [];
            $total = \is_array($body) ? (int) ($body['found'] ?? 0) : 0;

            foreach ($raw as $item) {
                $cursor++;

                if (!\is_array($item) || !Access::visible($item, $audiences)) {
                    continue;
                }

                $visible[] = $item;

                if (\count($visible) >= $limit) {
                    // Stop ON the boundary, so the cursor names the next unread
                    // asset rather than the end of the page it happened to be in.
                    return [
                        'results' => $visible,
                        'found' => $limit,
                        'more' => $cursor < $total,
                        'next_start' => $cursor,
                    ];
                }
            }

            if (!$raw || $cursor >= $total) {
                break;
            }

            // Ran out of requests with more left in Canto: say so rather than
            // pretend the library ended.
            $more = ($i === $requests - 1);
        }

        return [
            'results' => $visible,
            'found' => \count($visible),
            'more' => $more,
            'next_start' => $cursor,
        ];
    }

    /**
     * Every asset an external audience may see, in a compact form.
     *
     * Canto matches a keyword against custom field values, so searching for
     * "Distributor" and "Installer" returns every asset whose Portal Access
     * names them (checked against the whole library: all 1,140, none missing).
     * Each hit is then checked by Access::partner_visible, which drops the few
     * that merely contain the word elsewhere.
     *
     * Cached for 45 minutes. Canto's signed thumbnail links last a day, so a
     * stale index never holds a dead link. A new Portal Access value shows
     * after at most that long, or at once after Client::flush().
     *
     * Returns null when Canto cannot be reached, and caches nothing then.
     *
     * @return array<int,array<string,mixed>>|null
     */
    public static function partner_index(bool $force = false): ?array
    {
        if (!$force) {
            $cached = \get_transient(self::INDEX_TRANSIENT);

            if (\is_array($cached)) {
                return $cached;
            }
        }

        $found = [];

        foreach (['Distributor', 'Installer'] as $keyword) {
            for ($start = 0, $i = 0; $i < 10; $i++) {
                $body = self::get('search', ['keyword' => $keyword, 'limit' => 1000, 'start' => $start]);

                if (!\is_array($body) || !\is_array($body['results'] ?? null)) {
                    return null;
                }

                foreach ($body['results'] as $item) {
                    if (\is_array($item) && Access::partner_visible($item)) {
                        $compact = Access::compact($item);
                        $found[$compact['id']] = $compact;
                    }
                }

                $start += \count($body['results']);

                if (!$body['results'] || $start >= (int) ($body['found'] ?? 0)) {
                    break;
                }
            }
        }

        $index = \array_values($found);
        \usort($index, static fn ($a, $b) => \strnatcasecmp($a['name'], $b['name']));

        \set_transient(self::INDEX_TRANSIENT, $index, self::INDEX_TTL);

        return $index;
    }

    /**
     * Drop everything cached. Used by the panel's own refresh control.
     */
    public static function flush(): void
    {
        \delete_transient(self::TOKEN_TRANSIENT);
        \delete_transient(self::INDEX_TRANSIENT);
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
