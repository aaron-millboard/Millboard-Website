<?php

namespace Theme\WooCommerce;

/**
 * Stops browsers re-running a stored copy of the order-received (thank-you) page.
 *
 * ## The problem
 *
 * That page carries the inline GA4 `purchase` push. Measured on 19 Nov 2026: when the page
 * is run again later (Back, or a tab restored from the browser's own copy) it arrives in a
 * NEW anonymous GA4 session and GA4 keeps it as a second purchase. 33 orders between
 * 20 Jul and 18 Nov 2026 were counted twice this way, 78% of the audit-window cases in
 * Safari. The server-side once-per-order guard in mb-ga4-purchase never gets asked,
 * because the browser does not ask the server.
 *
 * WooCommerce 11.x deliberately strips `no-store` from the headers of its cart, checkout
 * and account pages for logged-out visitors (class-wc-cache-helper.php, "so that bfcache
 * is not blocked for visitors"). The order-received page is part of checkout, so it
 * loses it too. The plugin documents the way to put it back: add it in a later
 * `wp_headers` filter, which is this. WooCommerce strips it at priority 5, so this runs
 * at 20.
 *
 * ## Scope
 *
 * The order-received endpoint ONLY. Cart, checkout, basket and account keep WooCommerce's
 * own headers, so their back-button behaviour does not change.
 *
 * ## What this does not do
 *
 * It does not stop a replayed page_view, so the page-view driven GA4 events and the GTM
 * trigger 429 tags can still fire on a revisit. It only stops the stored page being
 * reused, so the server gets to answer and the once-per-order guard holds.
 */
class OrderReceivedCache
{
    public static function init(): void
    {
        \add_filter('wp_headers', [__CLASS__, 'add_no_store'], 20);
    }

    /**
     * No scalar type hint: filters can be handed odd values and this must never fatal.
     *
     * @param mixed $headers
     * @return mixed
     */
    public static function add_no_store($headers)
    {
        try {
            if (!\is_array($headers) || !\function_exists('is_wc_endpoint_url') || !\is_wc_endpoint_url('order-received')) {
                return $headers;
            }

            $current = isset($headers['Cache-Control']) ? (string) $headers['Cache-Control'] : '';
            if ($current === '' && \function_exists('wp_get_nocache_headers')) {
                $nocache = \wp_get_nocache_headers();
                $current = isset($nocache['Cache-Control']) ? (string) $nocache['Cache-Control'] : '';
            }

            $directives = \preg_split('/\s*,\s*/', \trim($current), -1, \PREG_SPLIT_NO_EMPTY);
            if (!\in_array('no-store', $directives, true)) {
                $directives[] = 'no-store';
            }
            $headers['Cache-Control'] = \implode(', ', $directives);
        } catch (\Throwable $e) {
            // A header tweak must never fail the page that has just taken someone's money.
        }

        return $headers;
    }
}
