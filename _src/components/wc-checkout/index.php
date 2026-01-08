<?php

$checkout = WC()->checkout();

// If checkout registration is disabled and not logged in, the user cannot checkout.
if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
    return;
}

?>

<form
    name="checkout"
    method="post"
    class="checkout woocommerce-checkout"
    action="<?php echo esc_url(wc_get_checkout_url()); ?>"
    enctype="multipart/form-data"
    aria-label="<?php echo esc_attr__('Checkout', 'woocommerce'); ?>"
>

    <?php if ($checkout->get_checkout_fields()) : ?>
        <div class="checkout__fields" id="customer_details">

            <!-- Contact Information Section -->
            <div class="checkout__section">

                <div class="checkout__section-header">
                    <h2><?php esc_html_e('Contact information', 'granola'); ?></h2>
                    <div><?php esc_html_e("We'll use this email to send you details and updates about your order.", 'granola'); ?></div>
                </div>

                <div class="checkout__section-fields">
                    <?php
                    $email_field = $checkout->get_checkout_fields('billing')['billing_email'];
                    woocommerce_form_field('billing_email', $email_field, $checkout->get_value('billing_email'));
                    ?>
                </div>

                <div>
                    <?php if (!is_user_logged_in() && $checkout->is_registration_enabled()) : ?>
                        <div class="woocommerce-account-fields">
                            <?php if (! $checkout->is_registration_required()) : ?>
                                <p class="form-row form-row-wide create-account">
                                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                                        <input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked(( true === $checkout->get_value('createaccount') || ( true === apply_filters('woocommerce_create_account_default_checked', false) ) ), true); ?> type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e('Create an account?', 'woocommerce'); ?></span>
                                    </label>
                                </p>

                            <?php endif; ?>

                            <?php if ($checkout->get_checkout_fields('account')) : ?>
                                <div class="create-account">
                                    <?php foreach ($checkout->get_checkout_fields('account') as $key => $field) : ?>
                                        <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
                                    <?php endforeach; ?>
                                    <div class="clear"></div>
                                </div>

                            <?php endif; ?>

                        </div>
                    <?php endif; ?>

                </div>

            </div>

            <!-- Billing Address Section -->
            <div class="checkout__section">

                <div class="checkout__section-header">
                    <h2><?php esc_html_e('Billing address', 'granola'); ?></h2>
                    <div><?php esc_html_e("Enter the address where you want your order delivered.", 'granola'); ?></div>
                </div>

                <div class="checkout__section-fields">

                    <?php
                    $fields = $checkout->get_checkout_fields('billing');
                    foreach ($fields as $key => $field) {
                        if ($key === 'billing_email') {
                            continue; // Skip email field as it's already rendered above
                        }
                        woocommerce_form_field($key, $field, $checkout->get_value($key));
                    }
                    ?>

                    <?php if (true === WC()->cart->needs_shipping_address()) : ?>
                        <div id="ship-to-different-address">
                            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                                <input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" <?php checked(apply_filters('woocommerce_ship_to_different_address_checked', 'shipping' === get_option('woocommerce_ship_to_destination') ? 1 : 0), 1); ?> type="checkbox" name="ship_to_different_address" value="1" /> <span><?php esc_html_e('Ship to a different address?', 'woocommerce'); ?></span>
                            </label>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

            <?php if (true === WC()->cart->needs_shipping_address()) : ?>
                <div class="checkout__section shipping_address woocommerce-shipping-fields__field-wrapper">

                    <div class="checkout__section-header">
                        <h2><?php esc_html_e('Shipping address', 'granola'); ?></h2>
                        <div><?php esc_html_e("Enter the address where you want your order delivered.", 'granola'); ?></div>
                    </div>

                    <div class="checkout__section-fields">

                        <?php
                        $fields = $checkout->get_checkout_fields('shipping');

                        foreach ($fields as $key => $field) {
                            woocommerce_form_field($key, $field, $checkout->get_value($key));
                        }
                        ?>
                    </div>

                </div>

            <?php endif; ?>

            <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
                <div class="checkout__section">

                    <div class="checkout__section-header">
                        <h2><?php esc_html_e('Shipping method', 'granola'); ?></h2>
                    </div>

                    <div class="checkout__section-shipping">

                        <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
                            <?php do_action('woocommerce_review_order_before_shipping'); ?>

                            <?php wc_cart_totals_shipping_html(); ?>

                            <?php do_action('woocommerce_review_order_after_shipping'); ?>

                        <?php endif; ?>
        
                    </div>

                </div>
            <?php endif; ?>

        </div>
    <?php endif; ?>

    <div class="checkout__summary">
        
        <div class="woocommerce__section-header" id="order_review_heading">
            <?php esc_html_e('Order summary', 'granola'); ?>
        </div>

        <?php woocommerce_order_review(); ?>

    </div>

    <div class="checkout__fields" id="payment_details">

        <?php woocommerce_checkout_payment(); ?>

    </div>

</form>