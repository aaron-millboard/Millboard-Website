<?php

namespace Granola\Components\InstallerProfileHeader;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'tagline' => '',
        'cover_image' => null,
        'badge_image' => null,
        'rating' => '',
        'review_count' => '',
        'reviews_url' => '',
        'quote_url' => '',
        'years_established' => '',
        'projects_completed' => '',
        'counties_covered' => '',
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'installer-profile-header',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Resolve the installer this header belongs to. The tier and the contact
    // details live on the Installer Details field group (post level), so read
    // them explicitly rather than relying on the block field context.
    // -------------------------------------------------------------------------
    $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : \get_the_ID();

    // Tier — Approved vs Advanced — is derived, never typed by hand. The
    // advanced tier gets a distinct layout (full-bleed hero + stats card).
    $is_advanced = !empty(\get_field('advanced_installer', $post_id));
    $args['is_advanced'] = $is_advanced;
    $args['tier'] = $is_advanced ? \__('Advanced', 'granola') : \__('Approved', 'granola');
    $args['classes'][] = $is_advanced
        ? 'installer-profile-header--advanced'
        : 'installer-profile-header--approved';

    // Eyebrow + "verified" pill wording follows the design per tier.
    $args['tier_label'] = $is_advanced
        ? \__('Millboard Advanced Installer', 'granola')
        : \__('Approved Millboard Installer', 'granola');
    $args['verified_label'] = \__('Verified Advanced Installer', 'granola');

    // The post title is the page H1.
    $args['title'] = \get_the_title($post_id);

    // Address line, from the Google Map field.
    $address = \get_field('address', $post_id);
    $args['address_text'] = (is_array($address) && !empty($address['address'])) ? $address['address'] : '';

    // Only show the rating when a score has been entered.
    $args['has_rating'] = ($args['rating'] !== '' && $args['rating'] !== null);

    // -------------------------------------------------------------------------
    // Advanced-tier stats bar. Only cells with data are shown.
    // -------------------------------------------------------------------------
    $stats = [];

    if ($args['years_established'] !== '' && $args['years_established'] !== null) {
        $stats[] = [
            'value' => $args['years_established'],
            'label' => \__('Years established', 'granola'),
        ];
    }

    if (!empty($args['projects_completed'])) {
        $stats[] = [
            'value' => $args['projects_completed'],
            'label' => \__('Projects completed', 'granola'),
        ];
    }

    if ($args['has_rating']) {
        $stats[] = [
            'value' => $args['rating'],
            'label' => \__('Rating', 'granola'),
            /* translators: %s: number of reviews. */
            'sublabel' => !empty($args['review_count']) ? sprintf(\__('%s reviews', 'granola'), $args['review_count']) : '',
        ];
    }

    if (!empty($args['counties_covered'])) {
        $stats[] = [
            'value' => $args['counties_covered'],
            'label' => \__('Counties covered', 'granola'),
        ];
    }

    $args['stats'] = $stats;

    // -------------------------------------------------------------------------
    // Hero call-to-action buttons (shared by both layouts).
    // -------------------------------------------------------------------------
    $phone = \get_field('phone', $post_id);

    $buttons = [];

    $buttons[] = [
        'text' => \__('Request a quote', 'granola'),
        'href' => !empty($args['quote_url']) ? $args['quote_url'] : '#installer-enquiry',
        'variant' => 'primary',
        'icon' => 'arrow',
    ];

    if (!empty($phone)) {
        $buttons[] = [
            'text' => \__('Call us', 'granola'),
            'href' => 'tel:' . preg_replace('/[^0-9+]/', '', $phone),
            'variant' => 'secondary',
            'icon' => 'phone',
        ];
    }

    $args['buttons'] = $buttons;

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
