<?php
$order = $args['order'] ?? null;
?>
<div class="thankyou">
    <?php if ($order) : ?>
        <div class="thankyou__details">
            <?php if ($order->has_status('failed')) : ?>
                <p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed">
                    <?php esc_html_e('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce'); ?></p>

                <p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
                    <a href="<?php echo esc_url($order->get_checkout_payment_url()); ?>" class="button pay"><?php esc_html_e('Pay', 'woocommerce'); ?></a>
                    <?php if (is_user_logged_in()) : ?>
                        <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="button pay"><?php esc_html_e('My account', 'woocommerce'); ?></a>
                    <?php endif; ?>
                </p>
            <?php else : ?>
                <div class="thankyou__details__header">
                    <h2><?php esc_html_e('Details', 'granola'); ?></h2>
                    <div><?php esc_html_e('Your details for this order can be found below and in your account.', 'granola'); ?></div>
                </div>

                <div class="thankyou__details__list">
                    <div class="thankyou__details__item">
                        <div class="label"><?php esc_html_e('Order number', 'woocommerce'); ?></div>
                        <div class="value"><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                    </div>

                    <div class="thankyou__details__item">
                        <div class="label"><?php esc_html_e('Billing address', 'woocommerce'); ?></div>
                        <div class="value">
                            <?php $billing_parts = array_filter([
                                $order->get_billing_address_1(),
                                $order->get_billing_address_2(),
                                $order->get_billing_city(),
                                $order->get_billing_postcode()
                            ]); ?>
                            <?php echo esc_html(implode(', ', $billing_parts)); ?>
                        </div>
                    </div>

                    <?php if ($order->has_shipping_address()) : ?>
                        <?php
                        $shipping_parts = array_filter([
                            $order->get_shipping_address_1(),
                            $order->get_shipping_address_2(),
                            $order->get_shipping_city(),
                            $order->get_shipping_postcode()
                        ]);
                        $billing_parts = array_filter([
                            $order->get_billing_address_1(),
                            $order->get_billing_address_2(),
                            $order->get_billing_city(),
                            $order->get_billing_postcode()
                        ]);
                        ?>
                        <?php if (implode(', ', $shipping_parts) !== implode(', ', $billing_parts)) : ?>
                            <div class="thankyou__details__item">
                                <div class="label"><?php esc_html_e('Shipping address', 'woocommerce'); ?></div>
                                <div class="value"><?php echo esc_html(implode(', ', $shipping_parts)); ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($order->get_payment_method_title()) : ?>
                        <div class="thankyou__details__item">
                            <div class="label"><?php esc_html_e('Payment method', 'woocommerce'); ?></div>
                            <div class="value"><?php echo wp_kses_post($order->get_payment_method_title()); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="thankyou__details__actions">
                    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="back-to-shop">
                        <?php esc_html_e('Back to website', 'granola'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="thankyou__review">
            <div class="woocommerce__section-header" id="order_review_heading">
                <?php esc_html_e('Order summary', 'granola'); ?>
            </div>

            <div class="checkout__summary__items">
                <?php foreach ($order->get_items() as $item_id => $item) { ?>
                    <?php $_product = $item->get_product(); ?>
                    <?php if ($_product && $_product->exists() && $item->get_quantity() > 0) { ?>
                        <div class="cart__item small">
                            <div class="cart__item__image">
                                <?php echo $_product->get_image(); ?>
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
                                        <?php echo wc_price($item->get_subtotal()); ?>
                                    </div>
                                </div>

                                <div class="cart__item__details__bottom">
                                    <div class="cart__item__details__bottom--left">
                                        <?php
                                        // show actual quantity
                                        echo '<div class="actual-quantity">' . esc_html__('Quantity: ', 'granola') . esc_html($item->get_quantity()) . ' packs</div>';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>

            <div class="thankyou__summary__totals">
                <div class="thankyou__summary__totals__item">
                    <div class="label"><?php esc_html_e('Subtotal', 'woocommerce'); ?></div>
                    <div class="value"><?php echo wc_price($order->get_subtotal()); ?></div>
                </div>

                <?php foreach ($order->get_coupon_codes() as $code) : ?>
                    <div class="thankyou__summary__totals__item">
                        <div class="label"><?php echo esc_html__('Coupon: ', 'woocommerce') . esc_html($code); ?></div>
                        <div class="value">-<?php echo wc_price($order->get_discount_total()); ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="thankyou__summary__totals__item">
                    <div class="label"><?php esc_html_e('Shipping', 'woocommerce'); ?></div>
                    <div class="value">
                        <?php
                        $shipping_total = $order->get_shipping_total();
                        if ($shipping_total > 0) {
                            echo wc_price($shipping_total);
                        } else {
                            esc_html_e('Free shipping', 'woocommerce');
                        }
                        ?>
                    </div>
                </div>

                <?php foreach ($order->get_fees() as $fee) : ?>
                    <div class="thankyou__summary__totals__item">
                        <div class="label"><?php echo esc_html($fee->get_name()); ?></div>
                        <div class="value"><?php echo wc_price($fee->get_total()); ?></div>
                    </div>
                <?php endforeach; ?>

                <?php if (wc_tax_enabled() && $order->get_total_tax() > 0) : ?>
                    <?php if ('itemized' === get_option('woocommerce_tax_total_display')) : ?>
                        <?php foreach ($order->get_tax_totals() as $code => $tax) : ?>
                            <div class="thankyou__summary__totals__item">
                                <div class="label"><?php echo esc_html($tax->label); ?></div>
                                <div class="value"><?php echo wp_kses_post($tax->formatted_amount); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="thankyou__summary__totals__item">
                            <div class="label"><?php echo esc_html(WC()->countries->tax_or_vat()); ?></div>
                            <div class="value"><?php echo wc_price($order->get_total_tax()); ?></div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="thankyou__summary__totals__item order-total">
                    <div class="label"><?php esc_html_e('Total', 'woocommerce'); ?></div>
                    <div class="value"><?php echo $order->get_formatted_order_total(); ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
