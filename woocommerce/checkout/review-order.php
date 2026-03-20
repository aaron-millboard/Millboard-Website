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

            // Set default unit name to item/items.
            $unit_name_singular = __('item', 'granola');
            $unit_name_plural = __('items', 'granola');

            // get this item attribute sample size
            // check calculator if variable
            if ($_product->is_type('variation')) {
                $parent_id = $_product->get_parent_id();
                $calculator_enabled = get_field('enable_calculator', $parent_id);
            } else {
                $calculator_enabled = get_field('enable_calculator', $_product->get_id());
            }

            $sample_size_attribute_name = \get_field('product_sample_size_taxonomy', 'options');
            $board_width_attribute_name = \get_field('product_board_width_taxonomy', 'options');

            $sample_size_attribute = $_product->get_attribute($sample_size_attribute_name ?? 'pa_sample-size');
            $board_width_attribute = $_product->get_attribute($board_width_attribute_name ?? 'pa_board-width');

            if ($sample_size_attribute || $board_width_attribute || $calculator_enabled) {
                // If board, we check by 3 signs: board width attribute, sample size attribute set to full or calculator enabled
                if ($board_width_attribute || $sample_size_attribute === 'Full' || $calculator_enabled) {
                    $unit_name_singular = __('board', 'granola');
                    $unit_name_plural = __('boards', 'granola');
                }

                // override if we have any sign of sample size
                if ($sample_size_attribute === 'Small' || $sample_size_attribute === 'Large') {
                    $unit_name_singular = __('sample', 'granola');
                    $unit_name_plural = __('samples', 'granola');
                }
            }

            // use singular unit name if quantity is 1
            if ($cart_item['quantity'] === 1) {
                $unit_name_plural = $unit_name_singular;
            }

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

                                <?php $attributes = \Theme\WooCommerce\Utils::get_product_display_attributes($_product); ?>
                                    <?php foreach ($attributes as $key => $attribute) { ?>
                                        <?php if (!empty($attribute)) { ?>
                                            <div class="cart__item__details__attribute cart__item__details__<?= esc_attr($attribute['name']); ?>">
                                                <?= esc_html($attribute['value']); ?>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>

                                <div class="product-price" data-title="<?php esc_attr_e('Price', 'woocommerce'); ?>">
                                    <?php echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key) . ' ' . esc_html__('per ', 'granola') . esc_html($unit_name_singular); ?>
                                </div>
                            </div>
                            <div class="cart__item__details__top--right">
                                <?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); ?>
                            </div>
                        </div>

                        <div class="cart__item__details__bottom">
                            <div class="cart__item__details__bottom--left">
                                <?php
                                // show actual quantity
                                echo '<div class="actual-quantity">' . esc_html__('Quantity: ', 'granola') . esc_html($cart_item['quantity']) . ' ' . esc_html($unit_name_plural) . '</div>';
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


        <?php if (WC()->cart->needs_shipping()) { ?>
            <div class="checkout__summary__totals__item">
                <div class="label"><?php esc_html_e('Shipping', 'woocommerce'); ?></div>
                <div class="value">
                    <?php if (WC()->cart->show_shipping() && WC()->cart->has_calculated_shipping()) {
                        // Get shipping total cost (incl. tax).
                        echo WC()->cart->get_cart_shipping_total();
                    } else {
                        esc_html_e('Taxes will be calculated after you enter your address', 'granola');
                    }
                    ?>
                </div>
            </div>
        <?php } ?>

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
