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

    $basket_count = 0;

    if (function_exists('WC') && WC()->cart) {
        if (is_multisite()) {
            $current_blog_id = get_current_blog_id();
            switch_to_blog($current_blog_id);
            $basket_count = WC()->cart->get_cart_contents_count();
            restore_current_blog();
        } else {
            $basket_count = WC()->cart->get_cart_contents_count();
        }
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
