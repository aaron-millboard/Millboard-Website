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
            'site-header__call-to-action-1',
        ];
    }

    // ---------------------------------------
    // Custom.
    // ---------------------------------------

    if (!empty($basket_count = WC()->cart->get_cart_contents_count())) {
        $args['content']['basket_button_content'] = '<span class="visually-hidden">' . esc_html__('Basket', 'granola') . '</span><span class="site-header__basket-count">' . esc_html($basket_count) . '</span>';
    } else {
        $args['content']['basket_button_content'] = '<span class="visually-hidden">' . esc_html__('Basket', 'granola') . '</span>';
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
