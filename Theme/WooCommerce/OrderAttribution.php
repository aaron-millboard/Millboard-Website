<?php

namespace Theme\WooCommerce;

/**
 * Restores WooCommerce order attribution capture on every locale.
 *
 * ## Why nothing was being captured
 *
 * WooCommerce stamps its hidden attribution inputs (`wc_order_attribution_*`: utm
 * source/medium/campaign, referrer, session entry, device type) onto ONE of five
 * checkout actions, chosen by the `wc_order_attribution_stamp_checkout_html_actions`
 * filter. `OrderAttributionController::on_init()` defaults that list to:
 *
 *   woocommerce_checkout_billing
 *   woocommerce_after_checkout_billing_form
 *   woocommerce_checkout_shipping
 *   woocommerce_after_order_notes
 *   woocommerce_checkout_after_customer_details
 *
 * The theme throws the stock checkout template away and renders
 * `\Granola\Component::get('wc-checkout')` instead, and that component fires NONE of
 * the five. The last one is still sitting commented out at index.php:123. So the
 * container is never output, nothing is POSTed, and `woocommerce_checkout_order_created`
 * saves empty attribution on every order, in every locale, ever since the theme went
 * live with the March 2026 migration.
 *
 * `woocommerce_checkout_before_order_review` IS fired by the component (index.php:133)
 * and, unlike the five defaults, sits INSIDE `<form class="checkout">`, so inputs
 * stamped there are actually submitted. Adding it to the list is the whole fix.
 *
 * ## Scope
 *
 * All locales, on Aaron's instruction (16 Sep 2026). It previously ran on en-us only,
 * via the `mb-order-attribution-us.php` mu-plugin, which was scoped to blog 5 while the
 * consent question for the other locales was open. Moving it here settles that and also
 * puts it under version control and CI rather than being a hand-placed file.
 *
 * ⚠️ **What widening this actually changes, because it is not "start tracking people".**
 * Sourcebuster already runs on every locale today and already sets its first-party
 * cookies there: CookieYes is not a plugin on this site, it is a CDN script the theme
 * enqueues (`assets/components/cookieyes/hooks.php:19`) with no `text/plain` marking and
 * no script-blocking integration, so it gates nothing server-side. The data is being
 * collected in the browser regardless. What changes is that it now gets PERSISTED onto
 * the order, which is to say onto a named customer record. That is a real change and it
 * is the part a DPO would want to have agreed, particularly for de-de and fr-fr.
 * Recorded here so the decision is visible next to the code rather than only in a ticket.
 *
 * ## The mu-plugin
 *
 * `mb-order-attribution-us.php` stays on the server. Its Part 1 is now redundant but
 * harmless: `add_action()` de-duplicates an identical callback on the same hook, and the
 * guard below keeps the action name from being listed twice in any case. Its Part 2
 * (exempting sourcebuster from WPConsent's Statistics blocklist) is still load-bearing
 * and has NOT been moved here, because WPConsent is active on en-us only and the filter
 * is WPConsent's own. Do not delete that file.
 */
class OrderAttribution
{
    /**
     * The action the theme's checkout component actually fires, inside the form element.
     *
     * If the component is ever restructured, this is the thing to re-check first: moving
     * the `do_action` outside `<form class="checkout">` silently empties every order's
     * attribution again, with no error anywhere.
     */
    private const STAMP_ACTION = 'woocommerce_checkout_before_order_review';

    public static function init(): void
    {
        \add_filter('wc_order_attribution_stamp_checkout_html_actions', [__CLASS__, 'add_stamp_action']);
    }

    /**
     * @param mixed $actions The action names WooCommerce will stamp the container onto.
     * @return mixed
     */
    public static function add_stamp_action($actions)
    {
        // Another filter has returned something unexpected. Hand it straight back rather
        // than replacing it with an array: breaking the checkout to add analytics would
        // be a bad trade.
        if (!\is_array($actions)) {
            return $actions;
        }

        if (!\in_array(self::STAMP_ACTION, $actions, true)) {
            $actions[] = self::STAMP_ACTION;
        }

        return $actions;
    }
}
