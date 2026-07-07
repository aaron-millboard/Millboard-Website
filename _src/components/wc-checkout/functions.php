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
