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
            <?php endif; ?>
        </div>

        <div class="thankyou__review">
            <div class="woocommerce__section-header" id="order_review_heading">
                <?php esc_html_e('Order summary', 'granola'); ?>
            </div>

            <div class="checkout__summary__items">
                <?php foreach ($order->get_items() as $item_id => $item) { ?>
                    <?php
                    $_product = $item->get_product();

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

                    $sample_size_attribute_name = \get_field('sample_size_taxonomy', 'options');
                    $board_width_attribute_name = \get_field('board_width_taxonomy', 'options');

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
                    ?>
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
                                        // Normalised product attribute array.
                                        // We have seen different return values for this but unclear why.
                                        // Normalise to avoid errors.
                                        $attributes = array_map(function ($attribute) {
                                            if ($attribute instanceof \WC_Product_Attribute) {
                                                $attr_options = $attribute->get_options();
                                                if (empty($attr_options)) {
                                                    return [];
                                                }

                                                $term_id = $attr_options[0];
                                                if (empty($term_id)) {
                                                    return [];
                                                }

                                                $term = get_term_by('term_id', $term_id, $attribute->get_name());
                                                return $term->slug;
                                            }

                                            return $attribute;
                                        }, $_product->get_attributes());

                                        // Remove sample size.
                                        unset($attributes['pa_sample-size']);

                                        // Sort pa_color attribute to first place.
                                        uksort($attributes, function ($attribute_name_1, $attribute_name_2) {
                                            if ($attribute_name_1 === 'pa_colour') {
                                                return -1;
                                            }

                                            if ($attribute_name_2 === 'pa_colour') {
                                                return 1;
                                            }

                                            return 0;
                                        });

                                        // Show other attributes
                                        foreach ($attributes as $key => $value) { ?>
                                            <?php if (!empty($value)) { ?>
                                                <?php $term = get_term_by('slug', $value, $key); ?>
                                                <?php if (!empty($term)) { ?>
                                                    <div class="cart__item__details__attribute cart__item__details__<?= esc_attr(str_replace('pa_', '', $value)); ?>">
                                                        <?= esc_html($term->name); ?>
                                                    </div>
                                                <?php } ?>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>

                                    <div class="cart__item__details__top--right">
                                        <?php echo wc_price($item->get_subtotal()); ?>
                                    </div>
                                </div>

                                <div class="cart__item__details__bottom">
                                    <div class="cart__item__details__bottom--left">
                                        <?php
                                        // show actual quantity
                                        echo '<div class="actual-quantity">' . esc_html__('Quantity: ', 'granola') . esc_html($item->get_quantity()) . ' ' . esc_html($unit_name_plural) . '</div>';
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
