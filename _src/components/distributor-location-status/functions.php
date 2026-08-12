<?php

namespace Granola\Components\DistributorLocationStatus;

/**
 * Status row for a distributor / showroom / experience-centre profile.
 *
 * Sits directly under the profile hero and carries the stockist / stock / display
 * badges and a live open-or-closed line. The hero owns the address, so the address
 * line here is off unless the block is used on its own.
 *
 * Everything is read from the record's own Distributor Details fields, so all 376
 * existing distributors render without editing. Of those only address, phone,
 * website and email are reliably populated today, so every badge beyond the stock
 * state has to degrade silently.
 */
function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        // The profile hero carries the address, so this is off by default and only
        // needed when the status block is used without it.
        'show_address' => false,
        'show_badges' => true,
        'show_status' => true,
    ], $args);

    $args['classes'] = array_merge([
        'distributor-location-status',
        'wp-block',
    ], $args['classes']);

    $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : \get_the_ID();
    if (!$post_id) {
        return empty($args['is_preview']) ? null : $args;
    }

    $address = \get_field('address', $post_id);

    $args['address'] = $args['show_address'] ? short_address($address) : '';

    // -------------------------------------------------------------------------
    // Badges.
    //
    // holds_stock is deliberately an explicit yes/no rather than a badge that
    // only appears when true, so a missing badge never has to be interpreted.
    // -------------------------------------------------------------------------
    $args['badges'] = [];

    if ($args['show_badges']) {
        if (\get_field('preferred_stockist', $post_id)) {
            $args['badges'][] = [
                'label' => \__('Stockist', 'granola'),
                'modifier' => 'stockist',
                'icon' => '',
                'dot' => false,
            ];
        }

        $args['badges'][] = \get_field('holds_stock', $post_id)
            ? [
                'label' => \__('Stock available', 'granola'),
                'modifier' => 'stock',
                'icon' => '',
                'dot' => true,
            ]
            : [
                'label' => \__('Stock to order', 'granola'),
                'modifier' => 'to-order',
                'icon' => '',
                'dot' => false,
            ];

        if (\get_field('has_display', $post_id)) {
            $args['badges'][] = [
                'label' => \__('Millboard display', 'granola'),
                'modifier' => 'display',
                'icon' => 'display',
                'dot' => false,
            ];
        }
    }

    $args['status'] = $args['show_status'] ? \Granola\Components\DistributorOpeningHours\today_status($post_id) : null;

    if (empty($args['address']) && empty($args['badges']) && empty($args['status']) && empty($args['is_preview'])) {
        return null;
    }

    return $args;
}

/**
 * Countries that write the postcode BEFORE the town, e.g. "54634 Bitburg".
 *
 * Listed by ISO code because `country` holds Google's localised name, which is
 * whatever language the record was geocoded in ("Deutschland" or "Germany",
 * "Schweiz/Suisse/Svizzera/Svizra", "Ελλάς"), and is far too unreliable to match on.
 */
const POSTCODE_FIRST = [
    'AT', 'BE', 'BG', 'CH', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR',
    'HU', 'IT', 'LT', 'LU', 'LV', 'MC', 'NL', 'NO', 'PL', 'PT', 'RO', 'RS', 'SE',
    'SI', 'SK', 'UA',
];

/**
 * Countries that put a state or province between the town and the postcode,
 * e.g. "Montgomery, NY 12549". Without it the town is ambiguous: there are
 * Montgomerys in more than a dozen US states.
 */
const STATE_BEFORE_POSTCODE = ['US', 'CA', 'AU', 'NZ'];

/**
 * Short, human address line, ordered the way the country it is in writes it.
 *
 * The stored google_map string is the full formatted address, which on records
 * carried over from Drupal is tab separated and repeats the street name, e.g.
 * "Unit DC4, Prologis Park\tMaylands Gateway\tHemel Hempstead\tHP2 4ZB\tUnited
 * Kingdom". The design wants the shorter "Yeomans Way, Bournemouth BH8 0BJ", so
 * prefer the structured components Google returned and only fall back to tidying
 * the raw string.
 *
 * The design is a UK example, and "town postcode" is a UK convention. Now that the
 * directory carries partners in 23 countries, following it everywhere printed
 * German addresses backwards ("Bitburg 54634" for what Germany writes as
 * "54634 Bitburg") and dropped the state from every US and Canadian record.
 */
function short_address($address): string
{
    if (!is_array($address)) {
        return '';
    }

    $street = trim((string) ($address['street_name'] ?? ''));
    $city = trim((string) ($address['city'] ?? ''));
    $postcode = trim((string) ($address['post_code'] ?? ''));
    $code = strtoupper(trim((string) ($address['country_short'] ?? '')));

    $locality = locality_line($city, $postcode, $code, $address);

    if ($street !== '' && $locality !== '') {
        return $street . ', ' . $locality;
    }

    if ($locality !== '') {
        return $locality;
    }

    return tidy_raw_address((string) ($address['address'] ?? ''));
}

/**
 * Town and postcode as one unit, in the local order.
 */
function locality_line(string $city, string $postcode, string $code, array $address): string
{
    if ($city === '' || $postcode === '') {
        // Nothing to order. Ireland is the common case: Eircode, no town.
        return trim($city . ' ' . $postcode);
    }

    if (in_array($code, POSTCODE_FIRST, true)) {
        return $postcode . ' ' . $city;
    }

    if (in_array($code, STATE_BEFORE_POSTCODE, true)) {
        // state_short is only populated for some countries, so fall back to the
        // full name rather than dropping the state altogether.
        $state = trim((string) ($address['state_short'] ?? '')) ?: trim((string) ($address['state'] ?? ''));

        return $state !== ''
            ? $city . ', ' . $state . ' ' . $postcode
            : $city . ' ' . $postcode;
    }

    // UK, Ireland and the Crown Dependencies, and the safe default.
    return $city . ' ' . $postcode;
}

/**
 * Collapse a tab-separated address into a comma-separated one, dropping the
 * consecutive duplicates the Drupal import produced ("Curtis Road\tCurtis Road").
 */
function tidy_raw_address(string $raw): string
{
    $parts = [];

    foreach (preg_split('~[\t\r\n]+~', $raw) as $part) {
        $part = trim(preg_replace('~\s{2,}~', ' ', $part), " ,\t");

        if ($part === '' || ($parts && strcasecmp(end($parts), $part) === 0)) {
            continue;
        }

        $parts[] = $part;
    }

    return implode(', ', $parts);
}
