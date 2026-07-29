<?php

namespace Granola\Components\Map;

function get_selected_post_types(array $args): array
{
    $post_types = [];

    if (!empty($args['sources'])) {
        if (is_array($args['sources'])) {
            $post_types = $args['sources'];
        } elseif (is_string($args['sources'])) {
            $post_types = array_map('trim', explode(',', $args['sources']));
        }
    }

    if (empty($post_types) && !empty($args['content_type']) && $args['content_type'] !== 'custom' && $args['content_type'] !== 'multiple') {
        if (is_array($args['content_type'])) {
            $post_types = $args['content_type'];
        } elseif (is_string($args['content_type'])) {
            $post_types = [$args['content_type']];
        }
    }

    return array_values(array_unique(array_filter(array_map('sanitize_key', $post_types))));
}

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'buttons' => [],
        'table_rows' => [],
        'content_type' => 'installer',
        'items' => [],
        'sidebar_heading' => [],
        'subtitle' => '',
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'map',
        'wp-block',
    ], $args['classes']);

    if ($args['content_type'] !== 'custom' && empty($args['items'])) {
        $args['items'] = get_item_data($args);

        // This used to name the content types, which on the distributor finder
        // now reads "Find Distributors, Experience Centres and Showrooms near
        // me" and dwarfs the controls beside it. The button sits inside the
        // directory it applies to, so a short fixed label is clearer.
        $args['search_geolocate_text'] = \__('Search near me', 'granola');
    }

    $results_count = count($args['items']);

    $args['sidebar_heading'] = [
        'el' => 'h3',
        'content' => sprintf(
            \_n(
                // translators: the number of map results.
                'Displaying: %1$s result',
                'Displaying: %1$s results',
                $results_count,
                'granola'
            ),
            number_format_i18n($results_count)
        ),
        'classes' => [
            'map__sidebar__heading',
        ],
    ];

    // Generate filters if multiple post types are present
    $args['filters'] = generate_filters($args);

    $args['search_submit'] = [
        'type' => 'submit',
        'content' => \__('Search', 'granola'),
        'classes' => [
            'g-button',
            'g-button--icon',
            'map__search__submit',
        ],
    ];

    // Selectable distance dropdown options.
    $args['distances'] = [
        10,
        15,
        25,
        50,
        100,
        150,
        250,
        500,
    ];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Returns a human-readable "location type" label for a map listing, used for
 * the listing tag / popup badge. Distributors and installers use their taxonomy
 * term (with a sensible default); Experience Centres and Showrooms use a fixed
 * label so customers can tell location types apart at a glance.
 */
function get_type_label(\WP_Post $wp_post, bool $is_advanced_installer = false): string
{
    switch ($wp_post->post_type) {
        case 'distributor':
            $terms = \get_the_terms($wp_post->ID, 'distributor_type');
            if (!empty($terms) && !\is_wp_error($terms)) {
                return $terms[0]->name;
            }
            return \_x('Stockist', 'Map listing type label', 'granola');

        case 'installer':
            $terms = \get_the_terms($wp_post->ID, 'installer_type');
            $label = (!empty($terms) && !\is_wp_error($terms)) ? $terms[0]->name : '';

            // The installer_type taxonomy only describes the specialism, and
            // every term is worded "Approved ...", so an advanced installer would
            // otherwise be badged "Approved" on the card and in the map popup.
            // Promote the tier while keeping the specialism, e.g.
            // "Approved Decking Installer" -> "Advanced Decking Installer".
            if ($is_advanced_installer) {
                if ($label === '') {
                    return \_x('Advanced Installer', 'Map listing type label', 'granola');
                }

                return preg_replace('/^Approved\b/i', \_x('Advanced', 'Installer tier', 'granola'), $label, 1);
            }

            return $label;

        case 'experience_centre':
            return \_x('Experience Centre', 'Map listing type label', 'granola');

        case 'showroom':
            return \_x('Showspace', 'Map listing type label', 'granola');
    }

    return '';
}

/**
 * Converts a "HH:MM" time to minutes past midnight, or null if unparseable.
 * Accepts a single-digit hour ("9:00") and tolerates stray whitespace.
 */
function time_to_minutes(string $time): ?int
{
    if (!preg_match('/^\s*(\d{1,2}):(\d{2})\s*$/', $time, $matches)) {
        return null;
    }

    $hours = (int) $matches[1];
    $minutes = (int) $matches[2];

    if ($hours > 23 || $minutes > 59) {
        return null;
    }

    return ($hours * 60) + $minutes;
}

/**
 * Builds a compact "today's opening hours" line from an opening_hours repeater.
 * Returns ['status' => 'open'|'closed'|'', 'text' => string]: "open" with an
 * "Open today HH:MM–HH:MM" line, "closed" with "Closed today", or empty status
 * and text when today has no matching row.
 */
function get_todays_opening_hours($rows): array
{
    $none = ['status' => '', 'text' => ''];

    if (empty($rows) || !is_array($rows)) {
        return $none;
    }

    $today = \current_time('l'); // Full day name, e.g. "Wednesday".

    foreach ($rows as $row) {
        if (!is_array($row) || ($row['day'] ?? '') !== $today) {
            continue;
        }

        if (!empty($row['closed'])) {
            return ['status' => 'closed', 'text' => \__('Closed today', 'granola')];
        }

        $open = trim((string) ($row['open'] ?? ''));
        $close = trim((string) ($row['close'] ?? ''));

        if ($open === '' || $close === '') {
            return $none;
        }

        // "Open" has to mean open right now, not merely open at some point
        // today, otherwise the line reads green at 9pm. Compare against site
        // local time in minutes.
        $open_minutes = time_to_minutes($open);
        $close_minutes = time_to_minutes($close);
        $now_minutes = time_to_minutes((string) \current_time('H:i'));

        if ($open_minutes === null || $close_minutes === null || $now_minutes === null) {
            return $none;
        }

        if ($now_minutes < $open_minutes) {
            return [
                'status' => 'closed',
                'text' => sprintf(
                    // translators: %s: opening time, e.g. "08:30".
                    \__('Closed now, opens %s', 'granola'),
                    $open
                ),
            ];
        }

        if ($now_minutes >= $close_minutes) {
            return ['status' => 'closed', 'text' => \__('Closed now', 'granola')];
        }

        return [
            'status' => 'open',
            'text' => sprintf(
                // translators: %s: closing time, e.g. "17:00".
                \__('Open now until %s', 'granola'),
                $close
            ),
        ];
    }

    return $none;
}

function get_item_data($args): array|null
{
    $items = [];

    // Determine which post types to query
    $post_types = get_selected_post_types($args);
    if (empty($post_types)) {
        return $items;
    }

    $post_query = new \WP_Query([
        'post_type' => $post_types,
        'posts_per_page' => 500, //arbitrary large number.
        'status' => 'publish',
        'perm' => 'readable',

        // Query optimisation.
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);

    foreach ($post_query->posts as $wp_post) {
        $wp_post_id = $wp_post->ID;
        $post_type = $wp_post->post_type;
        $address = \get_field('address', $wp_post_id);
        $lat = \get_field('address_lat', $wp_post_id);
        $lng = \get_field('address_lng', $wp_post_id);
        $advanced_installer = !empty(\get_field('advanced_installer', $wp_post_id));

        // Experience Centres and Showrooms are display venues by nature, so they
        // always hold a display regardless of the (distributor-only) flag.
        $has_display = !empty(\get_field('has_display', $wp_post_id))
            || in_array($post_type, ['experience_centre', 'showroom'], true);

        $preferred = !empty(\get_field('preferred_stockist', $wp_post_id));
        $holds_stock = !empty(\get_field('holds_stock', $wp_post_id));
        $today_hours = get_todays_opening_hours(\get_field('opening_hours', $wp_post_id));

        $items[] = [
            'id' => $wp_post_id,
            'title' => $wp_post->post_title,
            'address' => $address,
            'advanced_installer' => $advanced_installer,
            'phone' => \get_field('phone', $wp_post_id),
            'email' => \get_field('email', $wp_post_id),
            'website' => \get_field('website', $wp_post_id),
            'url' => \get_permalink($wp_post),
            'post' => $wp_post,
            'post_type' => $post_type,
            'type_label' => get_type_label($wp_post, $advanced_installer),
            // One key drives the map pin, the key swatch and the card badge icon
            // so all three always agree.
            'marker' => ($post_type === 'installer' && $advanced_installer) ? 'installer-advanced' : $post_type,
            'preferred' => $preferred,
            'has_display' => $has_display,
            'holds_stock' => $holds_stock,
            'display_collections' => \get_field('display_collections', $wp_post_id),
            'display_photo' => \get_field('display_photo', $wp_post_id),
            'opening_today' => $today_hours['text'],
            'opening_today_status' => $today_hours['status'],
            'attributes' => [
                'class' => 'map__listing',
                'data-map-item-lat' => $lat,
                'data-map-item-lng' => $lng,
                'data-map-item-post-type' => $post_type,
                'data-map-item-preferred' => $preferred ? '1' : null,
                'data-map-item-has-display' => $has_display ? '1' : null,
            ],
        ];
    }

    if ($args['content_type'] === 'installer') {
    // Sort everything alphabetically first.
        usort($items, static function (array $left, array $right): int {
            return strcasecmp($left['title'] ?? '', $right['title'] ?? '');
        });

    // Only the single (alphabetically first) advanced installer gets
    // pinned to the top of the list. Any other advanced installers stay
    // in their normal alphabetical position among the rest.
        $first_advanced_installer_index = null;

        foreach ($items as $index => $item) {
            if (!empty($item['advanced_installer'])) {
                $first_advanced_installer_index = $index;
                break;
            }
        }

        if ($first_advanced_installer_index !== null && $first_advanced_installer_index !== 0) {
            $first_advanced_installer = $items[$first_advanced_installer_index];
            unset($items[$first_advanced_installer_index]);
            array_unshift($items, $first_advanced_installer);
            $items = array_values($items);
        }
    }
    return $items;
}

function generate_filters($args): array
{
    $filters = [];

    // Only generate filters if there are multiple post types
    $post_types = get_selected_post_types($args);

    // A single-post-type installer map has no post types to split on, but the
    // two installer tiers are the equivalent categories, so it gets the same
    // filter bar and key as the distributor map (same markup, styles and JS —
    // the values just describe a tier instead of a post type).
    if (is_array($post_types) && $post_types === ['installer']) {
        return generate_installer_tier_filters($args);
    }

    // Check if we have multiple post types
    if (!is_array($post_types) || count($post_types) <= 1) {
        return $filters;
    }

    // Count items by post type
    $counts = [];
    if (!empty($args['items'])) {
        foreach ($args['items'] as $item) {
            $post_type = $item['post_type'] ?? ($item['post']->post_type ?? '');
            if (empty($post_type)) {
                continue;
            }
            if (!isset($counts[$post_type])) {
                $counts[$post_type] = 0;
            }
            $counts[$post_type]++;
        }
    }

    // Create "All" filter button
    $filters[] = [
        'label' => \esc_html__('All', 'granola'),
        'value' => '',
        'count' => count($args['items']),
        'active' => true,
    ];

    // Create filter button for each post type
    foreach ($post_types as $post_type) {
        $post_type_object = \get_post_type_object($post_type);
        if (!empty($post_type_object)) {
            $count = isset($counts[$post_type]) ? $counts[$post_type] : 0;
            $filters[] = [
                'label' => $post_type_object->label,
                'value' => $post_type,
                'count' => $count,
                'active' => false,
            ];
        }
    }

    return $filters;
}

/**
 * URL for a location-type icon.
 *
 * $variant 'marker' is the full map pin (used on the map and for the key
 * swatch); 'badge' is the small white glyph used inside the card's type badge.
 * Types with SVG artwork use it; anything else falls back to the legacy PNG pin.
 */
function marker_icon_url(string $marker, string $variant = 'marker'): string
{
    if ($marker === '') {
        return '';
    }

    $has_svg = in_array($marker, ['installer', 'installer-advanced', 'distributor', 'experience_centre', 'showroom'], true);
    $extension = $has_svg ? 'svg' : 'png';
    $file = $marker . '-marker.' . $extension;
    $url = \get_template_directory_uri() . '/assets/images/icons/' . $file;

    // Image assets aren't content-hashed like the CSS/JS bundles are, so a
    // recoloured pin would otherwise stay cached in browsers and at the edge.
    // Version by file mtime so new artwork actually shows up.
    $path = \get_template_directory() . '/assets/images/icons/' . $file;

    if (file_exists($path)) {
        $url = \add_query_arg('v', (string) filemtime($path), $url);
    }

    return $url;
}

/**
 * Inline partner mark for a location type (brand guide p.27: 24x24, line-based,
 * borderless). Drawn with currentColor so it takes the colour of whatever it
 * sits in — the card's type badge flips between light and dark fills, and the
 * glyph follows automatically rather than needing light/dark copies.
 */
function marker_icon_svg(string $marker, string $class = ''): string
{
    $glyphs = [
        'installer' => '<path d="M4 6.8L12 12L20 6.8" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="M4 12L12 17.2L20 12" fill="none" stroke="currentColor" stroke-width="2.2"/>',
        'installer-advanced' => '<path d="M4.4 5L12 9.4L19.6 5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4.4 9.8L12 14.2L19.6 9.8" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4.4 14.6L12 19L19.6 14.6" fill="none" stroke="currentColor" stroke-width="2"/>',
        'distributor' => '<rect x="3" y="5.5" width="18" height="3" fill="currentColor"/><rect x="3" y="10.5" width="18" height="3" fill="currentColor"/><rect x="3" y="15.5" width="18" height="3" fill="currentColor"/>',
        'experience_centre' => '<path d="M3 13.2L12 6L21 13.2" fill="none" stroke="currentColor" stroke-width="2.2"/><rect x="3" y="16.6" width="18" height="3" fill="currentColor"/>',
        'showroom' => '<rect x="4.2" y="5.4" width="15.6" height="13.2" fill="none" stroke="currentColor" stroke-width="2.2"/>',
    ];

    if (empty($glyphs[$marker])) {
        return '';
    }

    return sprintf(
        '<svg class="%s" width="12" height="12" viewBox="0 0 24 24" aria-hidden="true" focusable="false">%s</svg>',
        \esc_attr($class),
        $glyphs[$marker]
    );
}

/**
 * Filter bar + key for the installer map, split by tier rather than post type.
 *
 * Mirrors generate_filters() exactly so the shared markup, styles and filtering
 * script need no special-casing: each entry still has label / value / count /
 * active, plus a 'marker' for the key swatch (both tiers share the installer
 * pin, with the advanced one tinted via its own class).
 */
function generate_installer_tier_filters($args): array
{
    if (empty($args['items'])) {
        return [];
    }

    $advanced = 0;

    foreach ($args['items'] as $item) {
        if (!empty($item['advanced_installer'])) {
            $advanced++;
        }
    }

    $total = count($args['items']);
    $approved = $total - $advanced;

    // Nothing to split on if every installer sits in the same tier.
    if ($advanced === 0 || $approved === 0) {
        return [];
    }

    return [
        [
            'label' => \esc_html__('All', 'granola'),
            'value' => '',
            'count' => $total,
            'active' => true,
            'marker' => '',
        ],
        [
            'label' => \esc_html__('Approved Installer', 'granola'),
            'value' => 'installer-approved',
            'count' => $approved,
            'active' => false,
            'marker' => 'installer',
        ],
        [
            'label' => \esc_html__('Advanced Installer', 'granola'),
            'value' => 'installer-advanced',
            'count' => $advanced,
            'active' => false,
            'marker' => 'installer-advanced',
        ],
    ];
}

/**
 * Adds the Google API Key to an AJAX object's properties in granola-scripts via localization.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_localize_script/
 *
 * @param array $localizations An array of 'localizations' for granola-scripts.
 * @return array The filtered array of localizations for granola-scripts, with AJAX values conditionally added.
 */
function add_google_api_key_localization($localizations): array
{
    $api_key = \get_field('google_api_key', 'option');

    // Add Google API Key, if set.
    if (!empty($api_key)) {
        $localizations['google_api_key'] = $api_key;
    }

    $localizations['road_distances_endpoint'] = \rest_url('millboard/v1/road-distances');

    return $localizations;
}

/**
 * Registers the road-distances REST endpoint used by the map to re-rank
 * listings by driving distance instead of straight-line distance.
 */
function register_road_distances_endpoint(): void
{
    \register_rest_route('millboard/v1', '/road-distances', [
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\\handle_road_distances_request',
        'permission_callback' => '__return_true',
    ]);
}

/**
 * Handles a road-distances request: returns the driving distance and duration
 * from one origin to up to 25 destinations, in the order they were sent.
 *
 * Proxies the Google Routes API server-side (browser calls to it are blocked
 * by Google, and this keeps the key out of the page). Each origin-destination
 * pair is cached for 30 days, so radius/filter changes and repeat searches of
 * the same location cost no billable API elements.
 *
 * @param \WP_REST_Request $request The REST request.
 * @return \WP_REST_Response|\WP_Error The distances response, or an error.
 */
function handle_road_distances_request(\WP_REST_Request $request)
{
    $origin = parse_lat_lng($request['origin']);
    $raw_destinations = $request['destinations'];

    if (empty($origin) || !is_array($raw_destinations) || count($raw_destinations) < 1 || count($raw_destinations) > 25) {
        return new \WP_Error('invalid_params', 'Expected an origin and 1-25 destinations.', ['status' => 400]);
    }

    $destinations = [];
    foreach ($raw_destinations as $raw_destination) {
        $destination = parse_lat_lng($raw_destination);
        if (empty($destination)) {
            return new \WP_Error('invalid_params', 'Invalid destination coordinates.', ['status' => 400]);
        }
        $destinations[] = $destination;
    }

    $api_key = get_server_google_api_key();

    if (empty($api_key)) {
        return new \WP_Error('not_configured', 'No Google API key configured.', ['status' => 501]);
    }

    // Origins within ~100m share cache entries; that imprecision is
    // irrelevant to driving distances but lets nearby searches hit the cache.
    $origin_cache_key = round($origin['lat'], 3) . ',' . round($origin['lng'], 3);

    $results = [];
    $uncached = [];

    foreach ($destinations as $index => $destination) {
        $cache_key = 'mb_roaddist_' . md5($origin_cache_key . '|' . $destination['lat'] . ',' . $destination['lng']);
        $cached = \get_transient($cache_key);

        if (is_array($cached)) {
            $results[$index] = $cached;
        } else {
            $results[$index] = ['meters' => null, 'seconds' => null];
            $uncached[$index] = $destination;
        }
    }

    if (!empty($uncached)) {
        $matrix = fetch_google_route_matrix($origin, array_values($uncached), $api_key);

        if (is_array($matrix)) {
            foreach (array_keys($uncached) as $position => $index) {
                if (!isset($matrix[$position])) {
                    continue;
                }

                $results[$index] = $matrix[$position];

                $destination = $uncached[$index];
                $cache_key = 'mb_roaddist_' . md5($origin_cache_key . '|' . $destination['lat'] . ',' . $destination['lng']);
                \set_transient($cache_key, $matrix[$position], 30 * DAY_IN_SECONDS);
            }
        }
    }

    return \rest_ensure_response([
        'distances' => array_values($results),
    ]);
}

/**
 * Validates and normalises a lat/lng pair from request data.
 *
 * @param mixed $value The raw request value.
 * @return ?array ['lat' => float, 'lng' => float] or null if invalid.
 */
function parse_lat_lng($value): ?array
{
    if (!is_array($value) || !isset($value['lat']) || !isset($value['lng'])) {
        return null;
    }

    $lat = filter_var($value['lat'], FILTER_VALIDATE_FLOAT);
    $lng = filter_var($value['lng'], FILTER_VALIDATE_FLOAT);

    if ($lat === false || $lng === false || abs($lat) > 90 || abs($lng) > 180) {
        return null;
    }

    // 5 decimal places (~1m) is plenty and keeps cache keys stable.
    return ['lat' => round($lat, 5), 'lng' => round($lng, 5)];
}

/**
 * Returns the Google API key for server-side requests.
 *
 * The browser key (ACF option) is typically referrer-restricted, which Google
 * rejects for server calls, so a dedicated key can be provided via the
 * MILLBOARD_GOOGLE_SERVER_API_KEY constant (e.g. in wp-config.php).
 *
 * @return string The API key, or an empty string if none is configured.
 */
function get_server_google_api_key(): string
{
    if (defined('MILLBOARD_GOOGLE_SERVER_API_KEY') && MILLBOARD_GOOGLE_SERVER_API_KEY) {
        return (string) MILLBOARD_GOOGLE_SERVER_API_KEY;
    }

    return (string) \get_field('google_api_key', 'option');
}

/**
 * Fetches driving distances from the Google Routes API computeRouteMatrix
 * endpoint (basic routing, no traffic, so it bills at the Essentials rate).
 *
 * @link https://developers.google.com/maps/documentation/routes/compute_route_matrix
 *
 * @param array $origin ['lat' => float, 'lng' => float].
 * @param array $destinations Sequential array of ['lat' => float, 'lng' => float].
 * @param string $api_key The Google API key.
 * @return ?array Sequential array of ['meters' => ?int, 'seconds' => ?int]
 *                aligned with $destinations, or null on failure.
 */
function fetch_google_route_matrix(array $origin, array $destinations, string $api_key): ?array
{
    $to_waypoint = function (array $lat_lng): array {
        return [
            'waypoint' => [
                'location' => [
                    'latLng' => [
                        'latitude' => $lat_lng['lat'],
                        'longitude' => $lat_lng['lng'],
                    ],
                ],
            ],
        ];
    };

    $response = \wp_remote_post('https://routes.googleapis.com/distanceMatrix/v2:computeRouteMatrix', [
        'timeout' => 10,
        'headers' => [
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $api_key,
            'X-Goog-FieldMask' => 'originIndex,destinationIndex,distanceMeters,duration,condition',
        ],
        'body' => \wp_json_encode([
            'origins' => [$to_waypoint($origin)],
            'destinations' => array_map($to_waypoint, $destinations),
            'travelMode' => 'DRIVE',
        ]),
    ]);

    if (\is_wp_error($response) || \wp_remote_retrieve_response_code($response) !== 200) {
        return null;
    }

    $elements = json_decode(\wp_remote_retrieve_body($response), true);

    if (!is_array($elements)) {
        return null;
    }

    $results = array_fill(0, count($destinations), ['meters' => null, 'seconds' => null]);

    foreach ($elements as $element) {
        if (!isset($element['destinationIndex']) || !isset($results[$element['destinationIndex']])) {
            continue;
        }

        if (isset($element['condition']) && $element['condition'] !== 'ROUTE_EXISTS') {
            continue;
        }

        $results[$element['destinationIndex']] = [
            'meters' => isset($element['distanceMeters']) ? (int) $element['distanceMeters'] : null,
            // Durations come back as strings like "1795s".
            'seconds' => isset($element['duration']) ? (int) rtrim($element['duration'], 's') : null,
        ];
    }

    return $results;
}
