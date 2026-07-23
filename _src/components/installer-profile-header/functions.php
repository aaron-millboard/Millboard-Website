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

    // Tier — Approved vs Advanced — is derived, never typed by hand.
    $is_advanced = !empty(\get_field('advanced_installer', $post_id));
    $args['is_advanced'] = $is_advanced;
    $args['tier'] = $is_advanced ? \__('Advanced', 'granola') : \__('Approved', 'granola');
    $args['tier_label'] = sprintf(\__('%s Millboard Installer', 'granola'), $args['tier']);

    // The post title is the page H1.
    $args['title'] = \get_the_title($post_id);

    // Address line, from the Google Map field.
    $address = \get_field('address', $post_id);
    $args['address_text'] = (is_array($address) && !empty($address['address'])) ? $address['address'] : '';

    // -------------------------------------------------------------------------
    // Hero call-to-action buttons.
    // -------------------------------------------------------------------------
    $phone = \get_field('phone', $post_id);

    $buttons = [];

    $buttons[] = [
        'text' => \__('Request a quote', 'granola'),
        'href' => !empty($args['quote_url']) ? $args['quote_url'] : '#installer-enquiry',
        'variant' => 'primary',
    ];

    if (!empty($phone)) {
        $buttons[] = [
            'text' => \__('Call us', 'granola'),
            'href' => 'tel:' . preg_replace('/[^0-9+]/', '', $phone),
            'variant' => 'secondary',
        ];
    }

    $args['buttons'] = $buttons;

    // Only show the rating block when a score has been entered.
    $args['has_rating'] = ($args['rating'] !== '' && $args['rating'] !== null);

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
