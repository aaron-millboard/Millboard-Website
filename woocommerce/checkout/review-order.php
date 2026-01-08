<?php

/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

defined('ABSPATH') || exit;
?>
<div id="order_review" class="woocommerce-checkout-review-order shop_table woocommerce-checkout-review-order-table">

    <div class="checkout__summary__items">
        <?php
        do_action('woocommerce_review_order_before_cart_contents');

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);

            if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key)) {
                ?>

                <div class="<?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart__item small', $cart_item, $cart_item_key)); ?>">

                    <div class="cart__item__image">
                        <?php echo apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key); ?>
                    </div>

                    <div class="cart__item__details">
                        <div class="cart__item__details__top">
                            <div class="cart__item__details__top--left">
                                <div class="cart__item__details__name" data-title="<?php esc_attr_e('Product', 'woocommerce'); ?>">
                                    <?php echo $_product->get_title(); ?>
                                </div>
                                <?php
                                // check if have pa_colour attribute and display it
                                $attributes = $_product->get_attributes();
                                if (isset($attributes['pa_colour'])) {
                                    $term = get_term_by('slug', $attributes['pa_colour'], 'pa_colour');
                                    if (!empty($term)) {
                                        ?>
                                        <div class="cart__item__details__colour">
                                            <?php echo esc_html($term->name); ?>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                            <div class="cart__item__details__top--right">
                                <?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); ?>
                            </div>
                        </div>

                        <div class="cart__item__details__bottom">
                            <div class="cart__item__details__bottom--left">
                                <?php
                                // show actual quantity
                                echo '<div class="actual-quantity">' . esc_html__('Quantity: ', 'granola') . esc_html($cart_item['quantity']) . ' packs</div>';
                                ?>
                            </div>
                        </div>
                    </div>

                </div>
            
                <?php
            }
        }

        do_action('woocommerce_review_order_after_cart_contents');
        ?>
    </div>

    <?php /*
    <div class="checkout__summary__coupon">
        <?php woocommerce_checkout_coupon_form(); ?>
    </div>
    */ ?>

    <div class="checkout__summary__totals">

        <div class="checkout__summary__totals__item">
            <div class="label"><?php esc_html_e('Subtotal', 'woocommerce'); ?></div>
            <div class="value"><?php wc_cart_totals_subtotal_html(); ?></div>
        </div>

        <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
            <div class="checkout__summary__totals__item">
                <div class="label"><?php wc_cart_totals_coupon_label($coupon); ?></div>
                <div class="value"><?php wc_cart_totals_coupon_html($coupon); ?></div>
            </div>
        <?php endforeach; ?>


        <div class="checkout__summary__totals__item">
            <div class="label"><?php esc_html_e('Shipping', 'woocommerce'); ?></div>
            <div class="value">
                <?php
                if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) {
                    // get shipping total cost
                    $shipping_total = WC()->cart->get_shipping_total();
                    echo wc_price($shipping_total);
                } else {
                    esc_html_e('Free shipping', 'woocommerce');
                }
                ?>
            </div>
        </div>

        <?php foreach (WC()->cart->get_fees() as $fee) : ?>
            <div class="checkout__summary__totals__item">
                <div class="label"><?php echo esc_html($fee->name); ?></div>
                <div class="value"><?php wc_cart_totals_fee_html($fee); ?></div>
            </div>
        <?php endforeach; ?>

        <?php if (wc_tax_enabled() && ! WC()->cart->display_prices_including_tax()) : ?>
            <?php if ('itemized' === get_option('woocommerce_tax_total_display')) : ?>
                <?php foreach (WC()->cart->get_tax_totals() as $code => $tax) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
                    <div class="checkout__summary__totals__item">
                        <div class="label"><?php echo esc_html($tax->label); ?></div>
                        <div class="value"><?php echo wp_kses_post($tax->formatted_amount); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="checkout__summary__totals__item">
                    <div class="label"><?php echo esc_html(WC()->countries->tax_or_vat()); ?></div>
                    <div class="value"><?php wc_cart_totals_taxes_total_html(); ?></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="checkout__summary__totals__item order-total">
            <div class="label"><?php esc_html_e('Total', 'woocommerce'); ?></div>
            <div class="value"><?php wc_cart_totals_order_total_html(); ?></div>
        </div>

    </div>

</div>
