<?php

/**
 * Output a single payment method
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/payment-method.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.5.0
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="checkout__section wc_payment_method payment_method_<?php echo esc_attr($gateway->id); ?>">
    <input
        id="payment_method_<?php echo esc_attr($gateway->id); ?>"
        type="radio"
        class="input-radio"
        name="payment_method"
        value="<?php echo esc_attr($gateway->id); ?>"
        <?php checked($gateway->chosen, true); ?>
        data-order_button_text="<?php echo esc_attr($gateway->order_button_text); ?>"
    />

    <label class="checkout__section-header" for="payment_method_<?php echo esc_attr($gateway->id); ?>">
        <h2>
            <?php echo $gateway->get_title(); ?>
        </h2>
    </label>

    <?php if ($gateway->has_fields() || $gateway->get_description()) : ?>
        <div <?= \Granola\Helpers::build_attributes([
            'class' => 'payment_box payment_method_' . $gateway->id,
            'style' => !$gateway->chosen ? 'display:none;' : null,
        ]); ?>>
            <?php $gateway->payment_fields(); ?>
        </div>
    <?php endif; ?>
</div>
