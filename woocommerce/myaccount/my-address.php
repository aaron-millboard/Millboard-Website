<?php

/**
 * Addresses.
 *
 * Overridden from WooCommerce to match the 2026 account design: one hairline
 * card per address with an Edit link in its header.
 *
 * The design draws the edit form opening in place. WooCommerce edits an address
 * at its own URL (/my-account/edit-address/billing/), which is what the Edit
 * link goes to, and form-edit-address.php styles that page to match the panel
 * the design shows. Keeping Woo's URL means the form survives a refresh, can be
 * linked to from an email, and validates server-side as it already does.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined('ABSPATH') || exit;

$customer_id = get_current_user_id();

if (!wc_ship_to_billing_address_only() && wc_shipping_enabled()) {
    $get_addresses = apply_filters(
        'woocommerce_my_account_get_addresses',
        [
            'billing' => __('Billing address', 'woocommerce'),
            'shipping' => __('Shipping address', 'woocommerce'),
        ],
        $customer_id
    );
} else {
    $get_addresses = apply_filters(
        'woocommerce_my_account_get_addresses',
        [
            'billing' => __('Billing address', 'woocommerce'),
        ],
        $customer_id
    );
}

?>

<h2 class="mb-account-panel__title"><?php esc_html_e('Addresses', 'granola'); ?></h2>

<p class="mb-account-panel__intro">
    <?php esc_html_e('These addresses are used at checkout by default. You can always change them as you order.', 'granola'); ?>
</p>

<div class="mb-account-addresses">
    <?php foreach ($get_addresses as $name => $title) :
        $address = wc_get_account_formatted_address($name);
        ?>
        <div class="mb-account-addresses__card">
            <div class="mb-account-addresses__head">
                <h3 class="mb-account-addresses__title"><?php echo esc_html($title); ?></h3>
                <a
                    class="mb-account-addresses__edit"
                    href="<?php echo esc_url(wc_get_endpoint_url('edit-address', $name)); ?>"
                    aria-label="<?php
                        /* translators: %s: the address type, billing or shipping. */
                        echo esc_attr(sprintf(__('Edit %s', 'granola'), strtolower($title)));
                    ?>"
                >
                    <?php echo $address ? esc_html__('Edit', 'granola') : esc_html__('Add', 'granola'); ?>
                </a>
            </div>

            <address class="mb-account-addresses__body">
                <?php
                echo $address
                    ? wp_kses_post($address)
                    : esc_html__('You have not set up this address yet.', 'woocommerce');
                ?>
            </address>
        </div>
    <?php endforeach; ?>
</div>
