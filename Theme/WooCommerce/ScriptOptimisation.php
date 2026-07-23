<?php

namespace Theme\WooCommerce;

/**
 * Performance: Millboard does not use Apple Pay / Google Pay, so this disables
 * the Stripe express-checkout feature and keeps Stripe.js (and Stripe's remote
 * fraud script, js.stripe.com/clover/stripe.js) off every page except where a
 * card is actually entered (checkout, account/saved cards).
 *
 * The express-checkout element was what pulled Stripe.js (~240KB + significant
 * main-thread cost) onto product and cart pages, and it registers Stripe as a
 * footer script that is enqueued late (during content render), so it is
 * removed at both wp_enqueue_scripts and footer-print time.
 *
 * To change what counts as a "payment" page, hook `millboard_page_needs_stripe`.
 */
class ScriptOptimisation
{
    public static function init(): void
    {
        // Disable the express-checkout / payment-request feature at the settings
        // level so the element never renders or enqueues Stripe on product/cart.
        \add_filter('option_woocommerce_stripe_settings', [__CLASS__, 'disable_express_checkout']);
        \add_filter('wc_stripe_hide_payment_request_on_product_page', '__return_true');
        \add_filter('wc_stripe_show_payment_request_on_cart', '__return_false');
        \add_filter('wc_stripe_show_payment_request_on_checkout', '__return_false');

        // Dequeue any Stripe scripts left on non-payment pages. Run on both
        // hooks because the express element enqueues Stripe late (footer).
        \add_action('wp_enqueue_scripts', [__CLASS__, 'dequeue_stripe_off_payment_pages'], 100);
        \add_action('wp_print_footer_scripts', [__CLASS__, 'dequeue_stripe_off_payment_pages'], 0);
    }

    /**
     * @param mixed $settings
     * @return mixed
     */
    public static function disable_express_checkout($settings)
    {
        if (!is_array($settings)) {
            return $settings;
        }

        // Old + new keys: turn the feature off and clear all button locations.
        $settings['payment_request'] = 'no';
        $settings['express_checkout'] = 'no';
        $settings['express_checkout_button_locations'] = [];
        $settings['payment_request_button_locations'] = [];

        return $settings;
    }

    public static function dequeue_stripe_off_payment_pages(): void
    {
        if (\is_admin() || self::page_needs_stripe()) {
            return;
        }

        $scripts = \wp_scripts();

        if (!$scripts instanceof \WP_Scripts) {
            return;
        }

        foreach (array_keys($scripts->registered) as $handle) {
            if (self::is_stripe_handle((string) $handle, $scripts)) {
                \wp_dequeue_script($handle);
                \wp_deregister_script($handle);
            }
        }
    }

    private static function is_stripe_handle(string $handle, \WP_Scripts $scripts): bool
    {
        if (stripos($handle, 'stripe') !== false) {
            return true;
        }

        $src = isset($scripts->registered[$handle]) ? (string) $scripts->registered[$handle]->src : '';

        return stripos($src, 'stripe.com') !== false;
    }

    private static function page_needs_stripe(): bool
    {
        // If WooCommerce conditional tags are unavailable, do nothing (safe).
        if (!function_exists('is_checkout') || !function_exists('is_account_page')) {
            return true;
        }

        // Card entry happens at checkout; saved cards live on the account page.
        $needs = \is_checkout() || \is_account_page();

        return (bool) \apply_filters('millboard_page_needs_stripe', $needs);
    }
}
