<?php

namespace Theme\WooCommerce;

class CountryRestrictions
{
    private const FREE_SAMPLE_SHIPPING_RATE_ID = 'millboard_free_sample_shipping_homeowner';
    private const HOMEOWNER_MATCH_TERMS = [
        'homeowner',
        'house owner',
        'hausbesitzer',
        'hauseigentuemer',
        'hauseigentumer',
        'proprietaire',
        'proprietario',
        'propietario',
        'woningeigenaar',
        'huis eigenaar',
    ];
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
        \add_filter('woocommerce_cart_needs_payment', [__CLASS__, 'filter_cart_needs_payment'], 10, 2);
        \add_filter('woocommerce_cart_shipping_packages', [__CLASS__, 'inject_homeowner_flag_into_packages']);
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
        if (self::should_force_free_sample_shipping()) {
            $rate = self::build_free_sample_shipping_rate($package);

            return [
                self::FREE_SAMPLE_SHIPPING_RATE_ID => $rate,
            ];
        }

        if (!self::should_allow_worldwide_zero_cost_checkout()) {
            return self::apply_homeowner_sample_surcharge($rates, $package);
        }

        if (!empty($rates)) {
            return self::apply_homeowner_sample_surcharge($rates, $package);
        }

        $rate_id = 'millboard_zero_cost_shipping';

        $rates[$rate_id] = new \WC_Shipping_Rate(
            $rate_id,
            \__('Free shipping', 'granola'),
            0,
            [],
            $rate_id
        );

        return self::apply_homeowner_sample_surcharge($rates, $package);
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
     * @param array<string, mixed> $package
     * @return array<string, \WC_Shipping_Rate>
     */
    private static function apply_homeowner_sample_surcharge(array $rates, array $package): array
    {
        $surcharge = self::get_homeowner_small_sample_surcharge($package);

        if ($surcharge <= 0 || empty($rates)) {
            return $rates;
        }

        foreach ($rates as $rate_id => $rate) {
            if (!$rate instanceof \WC_Shipping_Rate) {
                continue;
            }

            $new_cost = (float) $rate->get_cost() + $surcharge;
            $rate->set_cost($new_cost);

            if ($rate->get_tax_status() === 'taxable') {
                $rate->set_taxes(\WC_Tax::calc_shipping_tax($new_cost, \WC_Tax::get_shipping_tax_rates()));
            }

            $rates[$rate_id] = $rate;
        }

        return $rates;
    }

    /**
     * @param array<string, mixed> $package
     */
    private static function get_homeowner_small_sample_surcharge(array $package): float
    {
        if (!self::cart_is_non_empty_and_zero_total()) {
            return 0.0;
        }

        if (!self::is_checkout_homeowner()) {
            return 0.0;
        }

        $has_small_sample = self::cart_contains_small_sample();

        if (!$has_small_sample) {
            $allow_metadata_fallback = (bool) apply_filters(
                'millboard/homeowner_small_sample_metadata_fallback',
                true,
                $package
            );

            if (!$allow_metadata_fallback || !self::cart_contains_only_zero_cost_items()) {
                return 0.0;
            }
        }

        $country = self::resolve_checkout_country($package);

        return match ($country) {
            'DE' => 6.0,
            'US' => 5.0,
            default => 0.0,
        };
    }

    /**
     * @param array<string, mixed> $package
     */
    private static function resolve_checkout_country(array $package): string
    {
        $country = self::normalize_country_code((string) ($package['destination']['country'] ?? ''));

        if ($country !== '') {
            return $country;
        }

        if (function_exists('WC') && \WC()->customer instanceof \WC_Customer) {
            $country = self::normalize_country_code((string) \WC()->customer->get_shipping_country());

            if ($country !== '') {
                return $country;
            }

            $country = self::normalize_country_code((string) \WC()->customer->get_billing_country());

            if ($country !== '') {
                return $country;
            }
        }

        if (function_exists('wp_unslash')) {
            $post_data = $_POST['post_data'] ?? null;

            if (is_string($post_data) && $post_data !== '') {
                $parsed = [];
                parse_str((string) wp_unslash($post_data), $parsed);

                $country = self::normalize_country_code((string) ($parsed['shipping_country'] ?? $parsed['billing_country'] ?? ''));

                if ($country !== '') {
                    return $country;
                }
            }

            $country = self::normalize_country_code((string) ($_POST['shipping_country'] ?? $_POST['billing_country'] ?? ''));

            if ($country !== '') {
                return $country;
            }
        }

        $country = self::normalize_country_code((string) get_option('woocommerce_default_country', ''));

        if ($country !== '') {
            return $country;
        }

        return '';
    }

    private static function normalize_country_code(string $country): string
    {
        $country = strtoupper(trim($country));

        if ($country === '') {
            return '';
        }

        if (preg_match('/^[A-Z]{2}/', $country, $matches) === 1) {
            return $matches[0];
        }

        return '';
    }

    private static function cart_is_non_empty_and_zero_total(): bool
    {
        if (!function_exists('WC') || !\WC()->cart instanceof \WC_Cart) {
            return false;
        }

        if (\WC()->cart->is_empty()) {
            return false;
        }

        return (float) \WC()->cart->get_cart_contents_total() === 0.0;
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
        if (!function_exists('wp_unslash')) {
            return false;
        }

        $post_data = $_POST['post_data'] ?? null;

        if (is_string($post_data) && $post_data !== '') {
            $parsed = [];
            parse_str((string) wp_unslash($post_data), $parsed);

            if (self::is_homeowner_selected($parsed)) {
                return true;
            }
        }

        $raw_posted = [];

        foreach ($_POST as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $raw_posted[$key] = is_string($value) ? wp_unslash($value) : $value;
        }

        return self::is_homeowner_selected($raw_posted);
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

            if (self::is_small_sample_product($product, is_array($cart_item) ? $cart_item : [])) {
                return true;
            }
        }

        return false;
    }

    private static function cart_contains_only_zero_cost_items(): bool
    {
        if (!function_exists('WC') || !\WC()->cart instanceof \WC_Cart) {
            return false;
        }

        $cart_items = \WC()->cart->get_cart();

        if (empty($cart_items)) {
            return false;
        }

        foreach ($cart_items as $cart_item) {
            if (!is_array($cart_item)) {
                return false;
            }

            $line_total = isset($cart_item['line_total']) ? (float) $cart_item['line_total'] : null;

            if ($line_total !== null && $line_total > 0.0) {
                return false;
            }

            $product = $cart_item['data'] ?? null;

            if ($product instanceof \WC_Product) {
                if ((float) $product->get_price() > 0.0) {
                    return false;
                }

                continue;
            }

            if ($line_total === null) {
                return false;
            }
        }

        return true;
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

    private static function should_force_free_sample_shipping(): bool
    {
        if (!self::cart_is_non_empty_and_zero_total()) {
            return false;
        }

        return self::is_checkout_homeowner();
    }

    /**
     * @param array<string, mixed> $package
     */
    private static function build_free_sample_shipping_rate(array $package): \WC_Shipping_Rate
    {
        $cost = self::get_homeowner_small_sample_surcharge($package);
        $taxes = [];

        if ($cost > 0) {
            $taxes = \WC_Tax::calc_shipping_tax($cost, \WC_Tax::get_shipping_tax_rates());
        }

        return new \WC_Shipping_Rate(
            self::FREE_SAMPLE_SHIPPING_RATE_ID,
            \__('Free Sample Shipping', 'granola'),
            $cost,
            $taxes,
            self::FREE_SAMPLE_SHIPPING_RATE_ID
        );
    }

    /**
     * @param array<string, mixed> $posted_data
     */
    private static function is_homeowner_selected(array $posted_data): bool
    {
        $possible_keys = [
            'who-am-i?',
            'who-am-i',
            'who_am_i',
            'billing_who-am-i?',
            'billing_who-am-i',
            'billing_who_am_i',
            'shipping_who-am-i?',
            'shipping_who-am-i',
            'shipping_who_am_i',
        ];

        foreach ($possible_keys as $key) {
            if (!array_key_exists($key, $posted_data)) {
                continue;
            }

            if (self::value_is_homeowner($posted_data[$key])) {
                return true;
            }
        }

        foreach ($posted_data as $key => $value) {
            $normalized_key = strtolower((string) $key);

            if (!str_contains($normalized_key, 'who') || !str_contains($normalized_key, 'am')) {
                continue;
            }

            if (self::value_is_homeowner($value)) {
                return true;
            }
        }

        return false;
    }

    private static function value_is_homeowner(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        $normalized = self::normalize_match_value($value);

        if ($normalized === '') {
            return false;
        }

        $terms = apply_filters('millboard/homeowner_match_terms', self::HOMEOWNER_MATCH_TERMS);

        if (!is_array($terms)) {
            $terms = self::HOMEOWNER_MATCH_TERMS;
        }

        foreach ($terms as $term) {
            if (!is_string($term)) {
                continue;
            }

            $normalized_term = self::normalize_match_value($term);

            if ($normalized_term === '') {
                continue;
            }

            if (str_contains($normalized, $normalized_term)) {
                return true;
            }
        }

        return false;
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
