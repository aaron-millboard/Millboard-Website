<?php

namespace Theme\WooCommerce;

/**
 * Performance: stop the WooCommerce Stripe gateway from loading Stripe.js (and,
 * in turn, Stripe's remote fraud/telemetry script) on pages where no payment
 * happens. Stripe is only needed on cart, checkout and account pages.
 *
 * On the homepage, product, collection and article templates this removes
 * ~240KB of JavaScript plus its main-thread cost, which PageSpeed flagged as
 * the single largest first-party-adjacent payload.
 *
 * NOTE: if Apple Pay / Google Pay express-checkout buttons are ever enabled on
 * product or cart pages, hook the `millboard_page_needs_stripe` filter to add
 * those conditions back in, e.g. return $needs || is_product().
 */
class ScriptOptimisation
{
    public static function init(): void
    {
        // Priority 100: run after the Stripe gateway has enqueued its scripts.
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
        if (!function_exists('is_checkout') || !function_exists('is_cart') || !function_exists('is_account_page')) {
            return true;
        }

        $needs = \is_checkout() || \is_cart() || \is_account_page();

        return (bool) \apply_filters('millboard_page_needs_stripe', $needs);
    }
}
