<?php

namespace Theme\WooCommerce;

/**
 * Who is told about a new order, by region.
 *
 * en-au: orders shipping to New South Wales go to TPD Timber, not Concept
 * Materials. Sam Cockeram passes them on, so the "New order" email goes to Sam
 * ALONE for those orders (Aaron, 21 Oct 2026). Every other Australian order
 * keeps WooCommerce's own recipient, orders.export.
 *
 * ⚠️ Concept Materials is the exclusive Australian distributor, see
 * RegionalPricing. This is a territory carve-out Sam has to have agreed.
 *
 * Only the email is routed. The HubSpot feeds (AU Live Contact / AU Live Order)
 * have no filters and are untouched, so the contact and order still reach
 * HubSpot exactly as before.
 */
class RegionalOrderRouting
{
    private const TPD_RECIPIENT = 'sam.cockeram@millboard.com';

    /**
     * NSW postcodes, as inclusive ranges. The ACT sits inside NSW's numbers
     * (2600-2618 and 2900-2920), so it is left out on purpose.
     */
    private const NSW_POSTCODES = [[2000, 2599], [2619, 2899], [2921, 2999]];

    public static function init(): void
    {
        \add_filter('woocommerce_email_recipient_new_order', [__CLASS__, 'route_new_order'], 10, 2);
    }

    /**
     * @param mixed $recipient Comma-separated, per WooCommerce.
     * @param mixed $order
     * @return mixed
     */
    public static function route_new_order($recipient, $order = null)
    {
        if (!$order instanceof \WC_Order || 'en-au' !== RegionalPricing::current_locale()) {
            return $recipient;
        }

        // Where it is delivered decides who serves it. Shipping is empty when
        // the checkout hides it, which is when billing is the delivery address.
        $ship = '' !== (string) $order->get_shipping_country();

        $nsw = $ship
            ? self::is_nsw((string) $order->get_shipping_country(), (string) $order->get_shipping_state(), (string) $order->get_shipping_postcode())
            : self::is_nsw((string) $order->get_billing_country(), (string) $order->get_billing_state(), (string) $order->get_billing_postcode());

        return $nsw ? self::TPD_RECIPIENT : $recipient;
    }

    /**
     * Whether an address is in New South Wales.
     *
     * The checkout's state is a dropdown, so "NSW" is what is expected. A
     * typed "New South Wales" is accepted too. The postcode is only consulted
     * when the state is blank, so a stated state always wins.
     */
    public static function is_nsw(string $country, string $state, string $postcode): bool
    {
        if ('AU' !== strtoupper(trim($country))) {
            return false;
        }

        $state = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $state));

        if ('' !== $state) {
            return 'NSW' === $state || 'NEWSOUTHWALES' === $state;
        }

        $postcode = (int) preg_replace('/\D/', '', $postcode);

        foreach (self::NSW_POSTCODES as [$from, $to]) {
            if ($postcode >= $from && $postcode <= $to) {
                return true;
            }
        }

        return false;
    }
}
