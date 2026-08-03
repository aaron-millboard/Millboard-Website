<?php

namespace Granola\Components\DistributorLocationStatus;

/**
 * Status row for a distributor / showroom / experience-centre profile.
 *
 * Sits directly under the theme page header, which the design keeps as-is, and
 * carries the short address line plus the stockist / stock / display badges and a
 * live open-or-closed line.
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
        'show_address' => true,
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
 * Short, human address line: street, town, postcode.
 *
 * The stored google_map string is the full formatted address, which on records
 * carried over from Drupal is tab separated and repeats the street name, e.g.
 * "Unit DC4, Prologis Park\tMaylands Gateway\tHemel Hempstead\tHP2 4ZB\tUnited
 * Kingdom". The design wants the shorter "Yeomans Way, Bournemouth BH8 0BJ", so
 * prefer the structured components Google returned and only fall back to tidying
 * the raw string.
 */
function short_address($address): string
{
    if (!is_array($address)) {
        return '';
    }

    $street = trim((string) ($address['street_name'] ?? ''));
    $city = trim((string) ($address['city'] ?? ''));
    $postcode = trim((string) ($address['post_code'] ?? ''));

    // Town and postcode read as one unit, as they do on an envelope.
    $locality = trim($city . ' ' . $postcode);

    if ($street !== '' && $locality !== '') {
        return $street . ', ' . $locality;
    }

    if ($locality !== '') {
        return $locality;
    }

    return tidy_raw_address((string) ($address['address'] ?? ''));
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
