<?php

namespace Theme\WooCommerce;

class SampleShipping
{
    private const SMALL_SAMPLE_MATCH_TERMS = [
        'small',
        'klein',
        'petit',
        'piccolo',
        'pequeno',
    ];

    public static function init(): void
    {
        \add_filter('woocommerce_countries_allowed_countries', [__CLASS__, 'filter_allowed_countries']);
        \add_filter('woocommerce_countries_shipping_countries', [__CLASS__, 'filter_shipping_countries']);
        \add_filter('woocommerce_cart_shipping_packages', [__CLASS__, 'inject_homeowner_flag_into_packages']);
        \add_filter('woocommerce_cart_needs_payment', [__CLASS__, 'filter_cart_needs_payment'], 10, 2);
        \add_filter('woocommerce_package_rates', [__CLASS__, 'filter_package_rates'], 100, 2);
        \add_action('woocommerce_checkout_update_order_review', [__CLASS__, 'capture_checkout_homeowner_selection']);
    }

    private const HOMEOWNER_SESSION_KEY = 'millboard_checkout_is_homeowner';

    /**
     * Skip payment collection for genuinely zero-total baskets.
     */
    public static function filter_cart_needs_payment(bool $needs_payment, $cart): bool
    {
        if (!$cart instanceof \WC_Cart) {
            return $needs_payment;
        }

        if (self::get_zero_cost_cart_total($cart) > 0.0) {
            return $needs_payment;
        }

        return false;
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
            return self::apply_sample_shipping_surcharge($rates);
        }

        if (!empty($rates)) {
            return self::apply_sample_shipping_surcharge($rates);
        }

        $rate_id = 'millboard_zero_cost_shipping';

        $rates[$rate_id] = new \WC_Shipping_Rate(
            $rate_id,
            \__('Free shipping', 'granola'),
            0,
            [],
            $rate_id
        );

        return self::apply_sample_shipping_surcharge($rates);
    }

    /**
     * Inject the current homeowner flag into each shipping package so the package hash
     * changes when the selection changes, busting WooCommerce's shipping rate cache.
     *
     * @param array<int, array<string, mixed>> $packages
     * @return array<int, array<string, mixed>>
     */
    public static function inject_homeowner_flag_into_packages(array $packages): array
    {
        $is_homeowner = function_exists('WC') && \WC()->session instanceof \WC_Session
            ? (bool) \WC()->session->get(self::HOMEOWNER_SESSION_KEY, false)
            : false;

        foreach ($packages as $key => $package) {
            $packages[$key]['millboard_is_homeowner'] = $is_homeowner;
        }

        return $packages;
    }

    public static function capture_checkout_homeowner_selection(string $posted_data): void
    {
        if (!function_exists('WC') || !\WC()->session instanceof \WC_Session) {
            return;
        }

        $parsed = [];
        parse_str($posted_data, $parsed);

        \WC()->session->set(self::HOMEOWNER_SESSION_KEY, self::is_homeowner_selected($parsed));
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

        return self::get_zero_cost_cart_total(\WC()->cart) === 0.0;
    }

    private static function get_zero_cost_cart_total(\WC_Cart $cart): float
    {
        return (float) \round((float) $cart->get_total('edit'), 2);
    }

    /**
     * @param array<string, \WC_Shipping_Rate> $rates
     * @return array<string, \WC_Shipping_Rate>
     */
    private static function apply_sample_shipping_surcharge(array $rates): array
    {
        $shipping_cost = self::get_sample_shipping_surcharge();

        if ($shipping_cost <= 0 || empty($rates)) {
            return $rates;
        }

        foreach ($rates as $rate_id => $rate) {
            if (!$rate instanceof \WC_Shipping_Rate) {
                continue;
            }

            $rate->set_label(__('Sample shipping', 'granola'));

            // The configured sample shipping value is the final amount to charge.
            $rate->set_cost($shipping_cost);

            if (method_exists($rate, 'set_tax_status')) {
                $rate->set_tax_status('none');
            }

            $rate->set_taxes([]);

            $rates[$rate_id] = $rate;
        }

        return $rates;
    }

    /**
     */
    private static function get_sample_shipping_surcharge(): float
    {
        if (!function_exists('WC') || !\WC()->cart instanceof \WC_Cart || \WC()->cart->is_empty()) {
            return 0.0;
        }

        if (!self::is_checkout_homeowner()) {
            return 0.0;
        }

        if (self::cart_contains_non_sample_products()) {
            return 0.0;
        }

        if (self::cart_contains_large_sample()) {
            $shipping_cost = self::get_large_sample_shipping_cost();

            return $shipping_cost ?? 0.0;
        }

        if (self::cart_contains_small_sample()) {
            $shipping_cost = self::get_small_sample_shipping_cost();

            return $shipping_cost ?? 0.0;
        }

        return 0.0;
    }

    private static function get_small_sample_shipping_cost(): ?float
    {
        $cost = function_exists('get_field') ? \get_field('small_sample_shipping', 'option') : null;

        if (!is_numeric($cost)) {
            return null;
        }

        $cost = (float) $cost;

        if ($cost < 0.0) {
            return null;
        }

        return (float) round($cost, 2);
    }

    private static function get_large_sample_shipping_cost(): ?float
    {
        $cost = function_exists('get_field') ? \get_field('large_sample_shipping', 'option') : null;

        if (!is_numeric($cost)) {
            return null;
        }

        $cost = (float) $cost;

        if ($cost < 0.0) {
            return null;
        }

        return (float) round($cost, 2);
    }

    private static function is_checkout_homeowner(): bool
    {
        if (function_exists('WC') && \WC()->session instanceof \WC_Session) {
            if ((bool) \WC()->session->get(self::HOMEOWNER_SESSION_KEY, false)) {
                return true;
            }
        }

        return self::is_homeowner_selected_in_request();
    }

    private static function is_homeowner_selected_in_request(): bool
    {
        return Audience::from_request() === Audience::CONSUMER;
    }

    private static function cart_contains_small_sample(): bool
    {
        if (!function_exists('WC') || !\WC()->cart instanceof \WC_Cart) {
            return false;
        }

        foreach (\WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'] ?? null;

            if (!$product instanceof \WC_Product) {
                continue;
            }

            if (Utils::is_sample($product) !== true) {
                continue;
            }

            if (self::is_small_sample_product($product, is_array($cart_item) ? $cart_item : [])) {
                return true;
            }
        }

        return false;
    }

    private static function cart_contains_large_sample(): bool
    {
        if (!function_exists('WC') || !\WC()->cart instanceof \WC_Cart) {
            return false;
        }

        foreach (\WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'] ?? null;

            if (!$product instanceof \WC_Product) {
                continue;
            }

            if (Utils::is_sample($product) !== true) {
                continue;
            }

            if (!self::is_small_sample_product($product, is_array($cart_item) ? $cart_item : [])) {
                return true;
            }
        }

        return false;
    }

    private static function cart_contains_non_sample_products(): bool
    {
        if (!function_exists('WC') || !\WC()->cart instanceof \WC_Cart) {
            return false;
        }

        foreach (\WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'] ?? null;

            if (!$product instanceof \WC_Product) {
                continue;
            }

            if (Utils::is_sample($product) !== true) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $cart_item
     */
    private static function is_small_sample_product(\WC_Product $product, array $cart_item = []): bool
    {
        $sample_size_taxonomy = \get_field('product_sample_size_taxonomy', 'options');
        $sample_size_values = [
            (string) $product->get_attribute($sample_size_taxonomy ?? 'pa_sample-size'),
            (string) $product->get_attribute('pa_sample-size'),
        ];

        $variation_values = $cart_item['variation'] ?? [];

        if (is_array($variation_values)) {
            foreach ($variation_values as $value) {
                if (is_string($value)) {
                    $sample_size_values[] = $value;
                }
            }
        }

        foreach ($sample_size_values as $sample_size_value) {
            if (self::matches_small_sample_size_value($sample_size_value)) {
                return true;
            }
        }

        $length = (float) $product->get_length();
        $width = (float) $product->get_width();

        if ($length <= 0 || $width <= 0) {
            return false;
        }

        return self::dimensions_match_small_sample($length, $width);
    }

    private static function matches_small_sample_size_value(string $value): bool
    {
        $normalized = self::normalize_match_value($value);

        if ($normalized === '') {
            return false;
        }

        foreach (self::SMALL_SAMPLE_MATCH_TERMS as $term) {
            $normalized_term = self::normalize_match_value($term);

            if ($normalized_term !== '' && str_contains($normalized, $normalized_term)) {
                return true;
            }
        }

        return str_contains($normalized, '100') && str_contains($normalized, '26');
    }

    private static function dimensions_match_small_sample(float $length, float $width): bool
    {
        return
            self::floats_match($length, 100.0) && self::floats_match($width, 26.0)
            || self::floats_match($length, 26.0) && self::floats_match($width, 100.0);
    }

    private static function floats_match(float $a, float $b): bool
    {
        return abs($a - $b) < 0.01;
    }

    /**
     * @param array<string, mixed> $posted_data
     */
    private static function is_homeowner_selected(array $posted_data): bool
    {
        return Audience::is_homeowner($posted_data);
    }

    private static function normalize_match_value(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = str_replace(['-', '_'], ' ', $normalized);

        if (function_exists('remove_accents')) {
            $normalized = remove_accents($normalized);
        }

        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return is_string($normalized) ? $normalized : '';
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
