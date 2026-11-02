<?php

namespace Theme\WooCommerce;

/**
 * Does this order ship somewhere THIS site actually sells to?
 *
 * Aaron, 2 Nov 2026, after a Swiss customer ordered a free sample on the
 * German site and landed on the German fulfilment sheet: "for all orders that
 * are placed on any of the sites can it look up the General options and if the
 * country matches a country we sell too then allow it on the site with the
 * normal flows. and if its for a country outside of the countries we sell too
 * on that site email it to orders.export@millboard.com".
 *
 * THREE THINGS ALREADY DECIDED THIS, AND THEY DISAGREED
 * -----------------------------------------------------
 * 1. WooCommerce "Sell to specific countries" governs who may check out, and
 *    SampleShipping deliberately overrides it so ANY country can order a free
 *    sample. That is wanted and is not changed here.
 * 2. The `millboard-order-routing` plugin reroutes the new-order e-mail, but
 *    against a HARDCODED list of ten territories that is the same on every
 *    site: GB IE JE GG IM DE AT FR US AU. So a French address on the UK site
 *    was treated as home turf and went to order.fulfilment.gb@, while a
 *    Lithuanian one was correctly rerouted.
 * 3. The German fulfilment queue had NO country rule at all, which is why the
 *    Swiss order appeared on the sheet even though its e-mail HAD been
 *    rerouted correctly.
 *
 * This class is the single answer all three can share. It reads each site's
 * own setting, so the UK selling to GB/IE/JE/GG/IM and Germany selling to
 * DE/AT are each judged on their own terms.
 *
 * ⚠️ READS THE RAW OPTIONS, NEVER WC()->countries->get_allowed_countries().
 * SampleShipping filters `woocommerce_countries_allowed_countries` to return
 * EVERY country when the basket is a free sample, and the new-order e-mail is
 * sent during checkout with that basket still in play. Going through
 * WooCommerce would hand back "every country", every order would look like
 * home turf, and this class would silently do nothing at all.
 *
 * ⚠️ FAILS OPEN. An order whose country cannot be determined counts as in
 * territory. A sample that reaches the wrong inbox is a nuisance; one that
 * vanishes off the fulfilment sheet with no trace is a lost customer.
 */
class Territory
{
    /**
     * After the routing plugin (20) and after the AU carve-out (10).
     *
     * Last word, deliberately. The plugin cannot see this site's setting, so
     * it under-reports; running afterwards means its answer can be completed
     * without editing a plugin that is not in this repository and would not
     * survive a reinstall.
     */
    public const EMAIL_PRIORITY = 25;

    public static function init(): void
    {
        \add_filter('woocommerce_email_recipient_new_order', [__CLASS__, 'route_new_order'], self::EMAIL_PRIORITY, 3);
    }

    /**
     * The countries this site sells to, as the "General options" screen has
     * them. An empty array means no restriction.
     *
     * @return string[]
     */
    public static function sells_to(): array
    {
        $mode = (string) \get_option('woocommerce_allowed_countries', 'all');

        if ('specific' === $mode) {
            return \array_values(\array_filter((array) \get_option('woocommerce_specific_allowed_countries', [])));
        }

        if ('all_except' === $mode) {
            $except = \array_filter((array) \get_option('woocommerce_all_except_countries', []));

            if (!$except || !\function_exists('WC')) {
                return [];
            }

            /*
             * get_countries() is the raw ISO list and is NOT the filtered
             * allowed-countries list, so it is safe to use here.
             */
            return \array_values(\array_diff(\array_keys((array) \WC()->countries->get_countries()), $except));
        }

        // 'all', or an option nobody has set.
        return [];
    }

    /**
     * Where the parcel is actually going.
     *
     * Shipping if there is one, billing otherwise. Nearly half the German
     * orders carry no shipping address at all -- 369 of 774 when this was
     * written -- so reading shipping alone would call them all unknown.
     */
    public static function delivery_country(\WC_Order $order): string
    {
        $country = (string) $order->get_shipping_country();

        if ('' === \trim($country)) {
            $country = (string) $order->get_billing_country();
        }

        return \strtoupper(\trim($country));
    }

    /**
     * @param \WC_Order|mixed $order
     */
    public static function is_in_territory($order): bool
    {
        if (!$order instanceof \WC_Order) {
            return true;
        }

        $sells_to = self::sells_to();

        // No restriction configured: everywhere is home.
        if (!$sells_to) {
            return true;
        }

        $country = self::delivery_country($order);

        // Fail open. See the class docblock.
        if ('' === $country) {
            return true;
        }

        return \in_array($country, $sells_to, true);
    }

    /**
     * Where an out-of-territory order should be told about.
     *
     * The routing plugin's own option, read rather than duplicated, so there
     * is still one field in wp-admin and the two cannot disagree about the
     * address.
     */
    public static function fallback_email(): string
    {
        $email = \trim((string) \get_option('mbor_fallback_email', ''));

        if ('' === $email && \is_multisite()) {
            $email = \trim((string) \get_site_option('mbor_network_fallback_email', ''));
        }

        return \is_email($email) ? $email : '';
    }

    /**
     * Send the new-order notification where this site's own setting says.
     *
     * @param mixed $recipient
     * @param mixed $order
     * @param mixed $email
     * @return mixed
     */
    public static function route_new_order($recipient, $order = null, $email = null)
    {
        if (!$order instanceof \WC_Order) {
            return $recipient;
        }

        $fallback = self::fallback_email();

        if ('' === $fallback) {
            return $recipient;
        }

        if (self::is_in_territory($order)) {
            /*
             * In territory, so the normal inbox should have it.
             *
             * Only a reroute to the FALLBACK is undone, never anything else.
             * RegionalOrderRouting sends New South Wales orders to Sam at
             * priority 10 and that is a deliberate carve-out inside Australia,
             * which is in territory: restoring blindly would quietly cancel
             * it.
             */
            if ($recipient === $fallback) {
                $configured = self::configured_recipient($email);

                return '' !== $configured ? $configured : $recipient;
            }

            return $recipient;
        }

        /*
         * Out of territory. If the plugin already did this, say nothing more:
         * it has written its own order note and a second one saying the same
         * thing helps nobody.
         */
        if ($recipient === $fallback) {
            return $recipient;
        }

        $order->add_order_note(
            \sprintf(
                /* translators: 1: ISO country code, 2: the site's selling countries, 3: fallback address. */
                \__('Delivery country %1$s is outside this site\'s selling countries (%2$s), so the new-order notification went to %3$s instead.', 'granola'),
                self::delivery_country($order),
                \implode(', ', self::sells_to()),
                $fallback
            ),
            false,
            true
        );

        return $fallback;
    }

    /**
     * What the WooCommerce settings say this e-mail's recipient should be.
     *
     * @param mixed $email
     */
    private static function configured_recipient($email): string
    {
        if ($email instanceof \WC_Email) {
            /*
             * $email->settings first. get_option() back-fills defaults into it
             * and an empty saved value then reads as the default rather than
             * as empty, which is the same trap the launch-email subjects hit.
             */
            $saved = isset($email->settings['recipient']) ? \trim((string) $email->settings['recipient']) : '';

            if ('' !== $saved) {
                return $saved;
            }
        }

        return (string) \get_option('admin_email', '');
    }
}
