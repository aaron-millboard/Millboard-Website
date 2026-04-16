<?php

namespace Theme\WooCommerce;

class CountryRestrictions
{
    public static function init(): void
    {
        \add_filter('woocommerce_countries_allowed_countries', [__CLASS__, 'filter_allowed_countries']);
        \add_filter('woocommerce_countries_shipping_countries', [__CLASS__, 'filter_shipping_countries']);
    }

    /**
     * Allow billing in any WooCommerce-supported country for zero-cost baskets.
     *
     * @param array<string, string> $countries
     * @return array<string, string>
     */
    public static function filter_allowed_countries(array $countries): array
    {
        if (!self::should_allow_worldwide_zero_cost_checkout()) {
            return $countries;
        }

        return self::merge_with_all_countries($countries);
    }

    /**
     * Allow shipping to any WooCommerce-supported country for zero-cost baskets.
     *
     * @param array<string, string> $countries
     * @return array<string, string>
     */
    public static function filter_shipping_countries(array $countries): array
    {
        if (!self::should_allow_worldwide_zero_cost_checkout()) {
            return $countries;
        }

        return self::merge_with_all_countries($countries);
    }

    private static function should_allow_worldwide_zero_cost_checkout(): bool
    {
        if (!function_exists('WC') || !\WC()->cart instanceof \WC_Cart) {
            return false;
        }

        if (\is_admin() && !\wp_doing_ajax() && !(defined('REST_REQUEST') && REST_REQUEST)) {
            return false;
        }

        if (!\is_cart() && !\is_checkout() && !\wp_doing_ajax() && !(defined('REST_REQUEST') && REST_REQUEST)) {
            return false;
        }

        if (\WC()->cart->is_empty()) {
            return false;
        }

        return (float) \WC()->cart->get_cart_contents_total() === 0.0;
    }

    /**
     * @param array<string, string> $countries
     * @return array<string, string>
     */
    private static function merge_with_all_countries(array $countries): array
    {
        $all_countries = \WC()->countries->get_countries();

        if (empty($all_countries)) {
            return $countries;
        }

        return array_replace($all_countries, $countries);
    }
}