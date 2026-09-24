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
 * Whether an address is in Ireland: the Republic, or a Northern Ireland (BT) postcode.
 *
 * @param string $country  Two-letter country code.
 * @param string $postcode Postcode, in any case and spacing.
 * @return bool True for an Irish address.
 */
function is_ireland_address(string $country, string $postcode): bool
{
    if ('IE' === $country) {
        return true;
    }

    $postcode = strtoupper(str_replace(' ', '', $postcode));

    return 'GB' === $country && 1 === preg_match('/^BT\d/', $postcode);
}

/**
 * Whether the customer's shipping address is in Ireland.
 *
 * @return bool True for an Irish delivery address.
 */
function is_ireland_destination(): bool
{
    if (!\WC()->customer) {
        return false;
    }

    return is_ireland_address(
        (string) \WC()->customer->get_shipping_country(),
        (string) \WC()->customer->get_shipping_postcode()
    );
}

/**
 * The message sending an Irish customer to the office, as there is no online delivery to Ireland.
 *
 * @return string The message, with the office number as a tel: link.
 */
function ireland_supply_message(): string
{
    return sprintf(
        /* translators: %s: office phone number link */
        \esc_html__("We can't deliver to Ireland through the online shop. Please contact our office on %s about supply to Ireland.", 'granola'),
        '<a href="tel:+442476439943">+44 24 7643 9943</a>'
    );
}

/**
 * Hook into 'woocommerce_no_shipping_available_html' so an Irish address is sent to the office rather than told to
 * check an address that is correct.
 *
 * @param string $html The default "no shipping options" message.
 * @return string The message, replaced for an Irish address.
 */
function ireland_no_shipping_message(string $html): string
{
    if (!is_ireland_destination()) {
        return $html;
    }

    return ireland_supply_message();
}

/**
 * Hook into 'woocommerce_after_checkout_validation' so Place order gives an Irish address the same office message,
 * in place of WooCommerce's "No shipping method has been selected". The checkout still fails: the error is swapped,
 * never removed, and any other shipping error is kept.
 *
 * @param array     $data   Posted checkout data.
 * @param \WP_Error $errors Checkout errors.
 */
function ireland_checkout_error($data, $errors): void
{
    if (!$errors instanceof \WP_Error || !in_array('shipping', $errors->get_error_codes(), true)) {
        return;
    }

    $data = (array) $data;
    $country = (string) ($data['shipping_country'] ?? '');
    $postcode = (string) ($data['shipping_postcode'] ?? '');

    if ('' === $country && \WC()->customer) {
        $country = (string) \WC()->customer->get_shipping_country();
        $postcode = (string) \WC()->customer->get_shipping_postcode();
    }

    if (!is_ireland_address($country, $postcode)) {
        return;
    }

    $no_method = \__('No shipping method has been selected. Please double check your address, or contact us if you need any help.', 'woocommerce');
    $messages = $errors->get_error_messages('shipping');

    if (!in_array($no_method, $messages, true)) {
        return;
    }

    $errors->remove('shipping');

    foreach ($messages as $message) {
        if ($message !== $no_method) {
            $errors->add('shipping', $message);
        }
    }

    $errors->add('shipping', ireland_supply_message());
}
