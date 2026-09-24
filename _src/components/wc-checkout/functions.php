<?php

namespace Granola\Components\WC_Checkout;

/**
 * Hook into 'woocommerce_update_order_review_fragments' to allow the shipping section fragment to be updated as part
 * of WC's 'update_order_review' ajax call.
 *
 * @param array $fragments The unfiltered fragments to be updated, keyed by query selector.
 * @return array The filtered fragment with the shipping output added.
 */
function update_shipping_methods_fragment(array $fragments): array
{
    $fragments['.checkout__section-shipping'] = (string) \Granola\Component::get('wc-checkout/shipping');
    return $fragments;
}

/**
 * Whether the customer's shipping address is in Ireland: the Republic, or a Northern Ireland (BT) postcode.
 *
 * @return bool True for an Irish delivery address.
 */
function is_ireland_destination(): bool
{
    if (!\WC()->customer) {
        return false;
    }

    $country = \WC()->customer->get_shipping_country();

    if ('IE' === $country) {
        return true;
    }

    $postcode = strtoupper(str_replace(' ', '', (string) \WC()->customer->get_shipping_postcode()));

    return 'GB' === $country && 1 === preg_match('/^BT\d/', $postcode);
}

/**
 * Hook into 'woocommerce_no_shipping_available_html' so an Irish address is sent to the office rather than told to
 * check an address that is correct. There is no online delivery to Ireland.
 *
 * @param string $html The default "no shipping options" message.
 * @return string The message, replaced for an Irish address.
 */
function ireland_no_shipping_message(string $html): string
{
    if (!is_ireland_destination()) {
        return $html;
    }

    return sprintf(
        /* translators: %s: office phone number link */
        \esc_html__("We can't deliver to Ireland through the online shop. Please contact our office on %s about supply to Ireland.", 'granola'),
        '<a href="tel:+442476439943">+44 24 7643 9943</a>'
    );
}
