<?php

namespace Theme\WooCommerce;

class CountryRestrictions
{
    public static function init(): void
    {
        \add_filter('woocommerce_countries_allowed_countries', [__CLASS__, 'filter_allowed_countries']);
        \add_filter('woocommerce_countries_shipping_countries', [__CLASS__, 'filter_shipping_countries']);
        \add_filter('woocommerce_package_rates', [__CLASS__, 'filter_package_rates'], 100, 2);
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

    /**
     * Add a fallback zero-cost shipping method for zero-cost baskets going to countries
     * outside the site's configured shipping markets.
     *
     * @param array<string, \WC_Shipping_Rate> $rates
     * @param array<string, mixed> $package
     * @return array<string, \WC_Shipping_Rate>
     */
    public static function filter_package_rates(array $rates, array $package): array
    {
        if (!self::should_allow_worldwide_zero_cost_checkout()) {
            return $rates;
        }

        if (!empty($rates)) {
            return $rates;
        }

        $rate_id = 'millboard_zero_cost_shipping';

        $rates[$rate_id] = new \WC_Shipping_Rate(
            $rate_id,
            \__('Free shipping', 'granola'),
            0,
            [],
            $rate_id
        );

        return $rates;
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

        $allowed_countries = array_replace($all_countries, $countries);
        $disallowed_countries = self::get_disallowed_free_sample_countries();

        if (empty($disallowed_countries)) {
            return $allowed_countries;
        }

        return array_diff_key($allowed_countries, array_flip($disallowed_countries));
    }

    /**
     * @return string[]
     */
    private static function get_disallowed_free_sample_countries(): array
    {
        if (!function_exists('get_field')) {
            return [];
        }

        $countries = \get_field('free_sample_disallowed_countries', 'option');

        if (!is_array($countries)) {
            return [];
        }

        return array_values(array_filter($countries, static fn ($country): bool => is_string($country) && $country !== ''));
    }
}
