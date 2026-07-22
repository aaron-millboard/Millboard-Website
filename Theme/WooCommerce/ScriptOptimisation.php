<?php

namespace Theme\WooCommerce;

/**
 * Performance: Millboard does not use Apple Pay / Google Pay, so this disables
 * the Stripe express-checkout buttons and keeps Stripe.js (and Stripe's remote
 * fraud script) off every page except where a card is actually entered.
 *
 * The express-checkout element was what pulled Stripe.js (~240KB + significant
 * main-thread cost) onto product and cart pages. With it disabled, Stripe is
 * only needed on the checkout and account (saved cards) pages. This removes it
 * from the homepage, products, collections and all content templates.
 *
 * To change what counts as a "payment" page, hook `millboard_page_needs_stripe`.
 */
class ScriptOptimisation
{
    public static function init(): void
    {
        // Disable the Apple/Google Pay express-checkout buttons everywhere
        // (unused by Millboard). These filters stop the express element from
        // loading Stripe on product and cart pages.
        \add_filter('wc_stripe_hide_payment_request_on_product_page', '__return_true');
        \add_filter('wc_stripe_show_payment_request_on_cart', '__return_false');
        \add_filter('wc_stripe_show_payment_request_on_checkout', '__return_false');

        // Belt-and-braces: dequeue any Stripe scripts still enqueued on pages
        // that do not take payment.
        \add_action('wp_enqueue_scripts', [__CLASS__, 'dequeue_stripe_off_payment_pages'], 100);
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
