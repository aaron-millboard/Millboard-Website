<?php

namespace Granola\Components\DistributorProfileHero;

/**
 * Hero for a distributor / showroom / experience-centre profile.
 *
 * Replaces the theme page header on these records, so site-main must list this
 * block in has_own_header() or the page ends up with two h1 elements.
 *
 * Per the design: breadcrumbs, a location eyebrow (town and county), the record
 * name, then the short address with a pin. All of it comes from the record's own
 * fields, so all 376 distributors render without editing.
 */
function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'preheading' => '',
        'heading' => '',
        'show_breadcrumbs' => true,
        'show_address' => true,
    ], $args);

    $args['classes'] = array_merge([
        'distributor-profile-hero',
        'wp-block',
    ], $args['classes']);

    $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : \get_the_ID();
    if (!$post_id) {
        return empty($args['is_preview']) ? null : $args;
    }

    if (empty($args['heading'])) {
        $args['heading'] = \get_the_title($post_id);
    }

    $address = \get_field('address', $post_id);

    // Location eyebrow, e.g. "Hemel Hempstead - Hertfordshire". Google returns the
    // county in `state`. Either half can be missing, so build from what is there
    // rather than printing a stray separator.
    if (empty($args['preheading'])) {
        $args['preheading'] = location_label($address);
    }

    // The status block below owns the badges; the address belongs up here with the
    // name, so it reuses that block's formatter rather than duplicating it.
    $args['address'] = $args['show_address']
        ? \Granola\Components\DistributorLocationStatus\short_address($address)
        : '';

    return $args;
}

/**
 * Town and county, separated by a middot, skipping whatever is unusable.
 *
 * The design shows "Bournemouth - Dorset". ACF's google_map `state` is Google's
 * administrative_area_level_1, which for UK addresses is the constituent country
 * ("England"), not the county, and ACF does not store level 2 at all. Printing
 * "Hemel Hempstead - England" is worse than printing the town alone, so the
 * constituent countries are skipped and those records show just the town.
 */
function location_label($address): string
{
    if (!is_array($address)) {
        return '';
    }

    $notCounties = ['england', 'scotland', 'wales', 'northern ireland'];

    $parts = [];

    foreach (['city', 'state'] as $key) {
        $value = trim((string) ($address[$key] ?? ''));

        if ($value === '' || in_array(strtolower($value), $notCounties, true)) {
            continue;
        }

        // Some imported records repeat the town as the county ("Isle of Man" for
        // both), which reads as a mistake, so only keep distinct values.
        if (in_array(strtolower($value), array_map('strtolower', $parts), true)) {
            continue;
        }

        $parts[] = $value;
    }

    return implode(" \u{00B7} ", $parts);
}
