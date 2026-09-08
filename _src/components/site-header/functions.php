<?php

namespace Granola\Components\SiteHeader;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'content' => [],
        'classes' => [],
        'help_center_link' => null,
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'site-header',
    ], $args['classes']);

    if ($header_call_to_action_1 = get_field('header_call_to_action_1', 'option')) {
        $args['content']['call_to_action_1'] = $header_call_to_action_1;
        $args['content']['call_to_action_1']['classes'] = [
            'g-button',
            'g-button--solid',
            'site-header__call-to-action-1',
        ];
    }

    if ($header_help_center_link = get_field('header_help_center_link', 'option')) {
        $args['help_center_link'] = $header_help_center_link;
    }

    // ---------------------------------------
    // Custom.
    // ---------------------------------------

    // Count PRODUCTS, not individual units. get_cart_contents_count() sums
    // quantities, so a real order reads as 109 or 325 in the badge and looks like
    // the basket has run away with itself (Elon, Sep 2026). A deck genuinely is
    // hundreds of boards and screws; the number people want next to the icon is
    // how many lines they have to review.
    //
    // Keep this in step with `cartCount` in the product-samples component, which
    // writes the same badge after an add without a page load.
    $basket_count = 0;

    if (function_exists('WC') && WC()->cart) {
        // The previous multisite branch here switched to the blog it was already
        // on, so both halves did the same thing.
        $basket_count = count(WC()->cart->get_cart());
    }

    if (!empty($basket_count)) {
        $args['content']['basket_button_content'] = '<span class="visually-hidden">' . esc_html__('Basket', 'granola') . '</span><span class="site-header__basket-count">' . esc_html($basket_count) . '</span>';
    } else {
        $args['content']['basket_button_content'] = '<span class="visually-hidden">' . esc_html__('Basket', 'granola') . '</span>';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
