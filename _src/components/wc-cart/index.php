<?php

/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.1.0
 */

defined('ABSPATH') || exit;

$show_quote_share = \Theme\WooCommerce\QuoteShare::is_quote_share_enabled();

// Notices here
do_action('woocommerce_before_cart');?>

<form class="cart woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
    <?php do_action('woocommerce_before_cart_table'); ?>

    <div class="cart__inner">

        <div class="woocommerce__section-header cart__header">
            <span class="woocommerce__section-header-item"><?php esc_html_e('Your items', 'granola'); ?></span>
            <span class="woocommerce__section-header-item"><?php esc_html_e('Total', 'granola'); ?></span>
        </div>

        <div class="cart__items shop_table" cellspacing="0">
            <?php do_action('woocommerce_before_cart_contents'); ?>

            <?php
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                /**
                 * Filter the product name.
                 *
                 * @since 2.1.0
                 * @param string $product_name Name of the product in the cart.
                 * @param array $cart_item The product in the cart.
                 * @param string $cart_item_key Key for the product in the cart.
                 */
                $product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
                $product_title = $_product->get_title();

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
                    if ($board_width_attribute || $calculator_enabled || !\Theme\WooCommerce\Utils::is_sample($_product)) {
                        $unit_name_singular = __('board', 'granola');
                        $unit_name_plural = __('boards', 'granola');
                    }

                    // override if we have any sign of sample size
                    if (\Theme\WooCommerce\Utils::is_sample($_product)) {
                        $unit_name_singular = __('sample', 'granola');
                        $unit_name_plural = __('samples', 'granola');
                    }
                }

                // use singular unit name if quantity is 1
                if ($cart_item['quantity'] === 1) {
                    $unit_name_plural = $unit_name_singular;
                }

                if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
                    $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                    ?>
                    <div class="<?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart__item', $cart_item, $cart_item_key)); ?>">

                        <div class="cart__item__image">
                            <?php
                            /**
                             * Filter the product thumbnail displayed in the WooCommerce cart.
                             *
                             * This filter allows developers to customize the HTML output of the product
                             * thumbnail. It passes the product image along with cart item data
                             * for potential modifications before being displayed in the cart.
                             *
                             * @param string $thumbnail     The HTML for the product image.
                             * @param array  $cart_item     The cart item data.
                             * @param string $cart_item_key Unique key for the cart item.
                             *
                             * @since 2.1.0
                             */
                            $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);

                            if (! $product_permalink) {
                                echo $thumbnail; // PHPCS: XSS ok.
                            } else {
                                printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail); // PHPCS: XSS ok.
                            }
                            ?>
                        </div>

                        <div class="cart__item__details">
                            <div class="cart__item__details__top">
                                <div class="cart__item__details__top--left">
                                    <div class="cart__item__details__name" data-title="<?php esc_attr_e('Product', 'woocommerce'); ?>">
                                        <?php
                                        if (! $product_permalink) {
                                            echo wp_kses_post($product_title . '&nbsp;');
                                        } else {
                                            echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $product_title), $cart_item, $cart_item_key));
                                        }
                                        ?>
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

                                    <div class="product-remove">
                                        <?php
                                            echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                'woocommerce_cart_item_remove_link',
                                                sprintf(
                                                    '<a role="button" href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">Remove item</a>',
                                                    esc_url(wc_get_cart_remove_url($cart_item_key)),
                                                    /* translators: %s is the product name */
                                                    esc_attr(sprintf(__('Remove %s from cart', 'woocommerce'), wp_strip_all_tags($product_name))),
                                                    esc_attr($product_id),
                                                    esc_attr($_product->get_sku())
                                                ),
                                                $cart_item_key
                                            );
                                        ?>
                                    </div>

                                </div>

                                <div class="cart__item__details__bottom--right">
                                    <div class="product-quantity" data-title="<?php esc_attr_e('Quantity', 'woocommerce'); ?>">
                                        <?php
                                        if (\Theme\WooCommerce\Utils::is_free_sample($_product)) {
                                            $min_quantity = 0;
                                            $max_quantity = 1;
                                        } elseif ($_product->is_sold_individually()) {
                                            $min_quantity = 1;
                                            $max_quantity = 1;
                                        } else {
                                            $min_quantity = 0;
                                            $max_quantity = $_product->get_max_purchase_quantity();
                                        }

                                        $product_quantity = woocommerce_quantity_input(
                                            array(
                                            'input_name'   => "cart[{$cart_item_key}][qty]",
                                            'input_value'  => $cart_item['quantity'],
                                            'max_value'    => $max_quantity,
                                            'min_value'    => $min_quantity,
                                            'product_name' => $product_name,
                                            ),
                                            $_product,
                                            false
                                        );

                                        echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item); // PHPCS: XSS ok.
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>

        </div>

    </div>

    <div class="cart__totals">

        <div class="woocommerce__section-header cart__header">
            <span class="woocommerce__section-header-item"><?php esc_html_e('Basket total', 'granola'); ?></span>
        </div>

        <div class="cart__totals__items <?php echo ( WC()->customer->has_calculated_shipping() ) ? 'calculated_shipping' : ''; ?>">


            <div class="cart__totals__item">
                <div class="cart__totals__item--label">
                    <?php esc_html_e('Subtotal', 'woocommerce'); ?>
                </div>
                <div class="cart__totals__item--value">
                    <?php wc_cart_totals_subtotal_html(); ?>
                </div>
            </div>

            <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
                <div class="cart__totals__item cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
                    <div class="cart__totals__item--label"><?php wc_cart_totals_coupon_label($coupon); ?></div>
                    <div class="cart__totals__item--value" data-title="<?php echo esc_attr(wc_cart_totals_coupon_label($coupon, false)); ?>"><?php wc_cart_totals_coupon_html($coupon); ?></div>
                </div>
            <?php endforeach; ?>

            <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
                <div class="cart__totals__item shipping">
                    <div class="cart__totals__item--label">
                        <?php esc_html_e('Delivery', 'granola'); ?>
                    </div>
                    <div class="cart__totals__item--value-plain">
                        <?php echo esc_html__('Calculated at checkout', 'granola'); ?>
                        <?php //woocommerce_shipping_calculator(); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach (WC()->cart->get_fees() as $fee) : ?>
                <div class="cart__totals__item fee">
                    <div class="cart__totals__item--label">
                        <?php echo esc_html($fee->name); ?>
                    </div>
                    <div class="cart__totals__item--value">
                        <?php wc_cart_totals_fee_html($fee); ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php
            if (wc_tax_enabled() && ! WC()->cart->display_prices_including_tax()) {
                $taxable_address = WC()->customer->get_taxable_address();
                $estimated_text  = '';

                if (WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping()) {
                    /* translators: %s location. */
                    $estimated_text = sprintf(' <small>' . esc_html__('(estimated for %s)', 'woocommerce') . '</small>', WC()->countries->estimated_for_prefix($taxable_address[0]) . WC()->countries->countries[ $taxable_address[0] ]);
                }

                if ('itemized' === get_option('woocommerce_tax_total_display')) {
                    foreach (WC()->cart->get_tax_totals() as $code => $tax) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                        ?>
                        <div class="cart__totals__item tax-rate-<?php echo esc_attr(sanitize_title($code)); ?>">
                            <div class="cart__totals__item--label">
                                <?php echo esc_html($tax->label) . $estimated_text; ?>
                            </div>
                            <div class="cart__totals__item--value">
                                <?php echo wp_kses_post($tax->formatted_amount); ?>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="cart__totals__item tax-total">
                        <div class="cart__totals__item--label">
                            <?php echo esc_html(WC()->countries->tax_or_vat()) . $estimated_text; ?>
                        </div>
                        <div class="cart__totals__item--value" data-title="<?php echo esc_attr(WC()->countries->tax_or_vat()); ?>">
                            <?php wc_cart_totals_taxes_total_html(); ?>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>

                <?php do_action('woocommerce_cart_totals_before_order_total'); ?>

                <div class="cart__totals__item order-total">
                    <div class="cart__totals__item--label">
                        <?php esc_html_e('Total', 'woocommerce'); ?>
                    </div>
                    <div class="cart__totals__item--value" data-title="<?php esc_attr_e('Total', 'woocommerce'); ?>">
                        <?php wc_cart_totals_order_total_html(); ?>
                    </div>
                </div>

                <?php if ($show_quote_share) :
                    $quote_form_action = esc_url(admin_url('admin-post.php'));
                    $quote_snapshot = [
                        'items' => [],
                        'lines' => [],
                        'total' => wp_strip_all_tags(html_entity_decode(WC()->cart->get_total(), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                        'total_raw' => (string) round((float) WC()->cart->get_total('edit'), 2),
                    ];

                    foreach (WC()->cart->get_cart() as $quote_item) {
                        if (empty($quote_item['data']) || !$quote_item['data'] instanceof \WC_Product) {
                            continue;
                        }

                        $quote_quantity = isset($quote_item['quantity']) ? (int) $quote_item['quantity'] : 0;

                        if ($quote_quantity < 1) {
                            continue;
                        }

                        $quote_snapshot['lines'][] = sprintf('%s x %d', wp_strip_all_tags($quote_item['data']->get_name()), $quote_quantity);

                        $quote_snapshot['items'][] = [
                            'product_id' => isset($quote_item['product_id']) ? (int) $quote_item['product_id'] : 0,
                            'variation_id' => isset($quote_item['variation_id']) ? (int) $quote_item['variation_id'] : 0,
                            'quantity' => $quote_quantity,
                            'variation' => isset($quote_item['variation']) && is_array($quote_item['variation']) ? $quote_item['variation'] : [],
                        ];
                    }

                    $quote_snapshot_encoded = base64_encode(wp_json_encode($quote_snapshot));
                    ?>
                <section class="cart__quote-share" aria-label="<?php esc_attr_e('Share quote', 'granola'); ?>">
                    <p class="cart__quote-share__copy"><?php esc_html_e('Want to share this quote?', 'granola'); ?></p>
                    <button
                        type="button"
                        class="button cart__quote-share__open"
                        data-quote-share-open
                        aria-haspopup="dialog"
                        aria-controls="quote-share-modal"
                    >
                        <?php esc_html_e('SHARE & SAVE QUOTE', 'granola'); ?>
                    </button>
                </section>
                <?php endif; ?>

                <?php do_action('woocommerce_cart_totals_after_order_total'); ?>

            </table>

            <?php do_action('woocommerce_after_cart_totals'); ?>

        </div>

        <div>
            <?php /*
            <?php if (wc_coupons_enabled()) { ?>
                <div class="coupon">
                    <label for="coupon_code" class="visually-hidden"><?php esc_html_e('Coupon:', 'woocommerce'); ?></label> <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e('Coupon code', 'woocommerce'); ?>" /> <button type="submit" class="button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="apply_coupon" value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>"><?php esc_html_e('Apply coupon', 'woocommerce'); ?></button>
                    <?php do_action('woocommerce_cart_coupon'); ?>
                </div>
            <?php } ?>

            */ ?>

        </div>

    </div>

    <div class="cart__actions">
        <div class="wc-order-essentials-link">
            <a href="<?php echo esc_url(\Theme\WooCommerce\OrderEssentials::get_order_essentials_url()); ?>" class="button">
                <?php esc_html_e('Review order essentials', 'granola'); ?>
            </a>
        </div>

        <div class="wc-proceed-to-checkout">
            <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="checkout-button button alt wc-forward<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>">
                <?php esc_html_e('Proceed to checkout', 'woocommerce'); ?>
            </a>
        </div>

        <button type="submit" class="button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="update_cart" value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>"><?php esc_html_e('Update cart', 'woocommerce'); ?></button>

        <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>

        <?php do_action('woocommerce_cart_actions'); ?>

    </div>

</form>

<?php if ($show_quote_share) : ?>
<div
    id="quote-share-modal"
    class="cart-quote-modal"
    data-quote-share-modal
    hidden
    aria-hidden="true"
>
    <div class="cart-quote-modal__overlay" data-quote-share-close></div>
    <div
        class="cart-quote-modal__dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="quote-share-modal__title"
    >
            <button type="button" class="cart-quote-modal__close" data-quote-share-close aria-label="<?php esc_attr_e('Close modal', 'granola'); ?>">
                <?= esc_html__('Close', 'granola'); ?>
                <?= \Granola\SVG::get('icons/cross.svg'); ?>
            </button>

        <h2 class="cart-quote-modal__title" id="quote-share-modal__title"><?php esc_html_e('Generate Quote', 'granola'); ?></h2>

        <h3 class="cart-quote-modal__subtitle"><?php esc_html_e('Enter details', 'granola'); ?></h3>

        <form class="cart-quote-modal__form" action="<?php echo $quote_form_action; ?>" method="post">
            <input type="hidden" name="action" value="millboard_quote_submit">
            <input type="hidden" name="quote_snapshot" value="<?php echo esc_attr($quote_snapshot_encoded); ?>">
            <?php wp_nonce_field('millboard_quote_submit', 'millboard_quote_nonce'); ?>

            <label>
                <?php esc_html_e('Company name', 'granola'); ?>
                <input type="text" name="company_name" required>
            </label>

            <label>
                <?php esc_html_e('Contact name', 'granola'); ?>
                <input type="text" name="contact_name" required>
            </label>

            <label>
                <?php esc_html_e('Email address', 'granola'); ?>
                <input
                    type="email"
                    name="email_address"
                    required
                    maxlength="254"
                    autocomplete="email"
                    pattern="^[^\s@]+@[^\s@]+\.[^\s@]{2,}$"
                    title="Please enter a valid email address"
                >
            </label>

            <label>
                <?php esc_html_e('Phone number', 'granola'); ?>
                <input type="tel" name="phone_number" required>
            </label>

                        <label>
                <?php esc_html_e('Rep email address', 'granola'); ?>
                <input
                    type="email"
                    name="rep_email_address"
                    maxlength="254"
                    autocomplete="email"
                    pattern="^[^\s@]+@[^\s@]+\.[^\s@]{2,}$"
                    title="Please enter a valid email address"
                >
            </label>

            <label class="cart-quote-modal__customer-reference-number">
                <?php esc_html_e('Customer reference number', 'granola'); ?>
                <input type="text" name="customer_reference_number" required>
            </label>

            <label>
                <?php esc_html_e('Sales notes', 'granola'); ?>
                <textarea name="sales_notes" rows="4"></textarea>
            </label>

            <div class="cart-quote-modal__actions">
                <button type="submit" class="g-button" name="quote_intent" value="email"><?php esc_html_e('Email Quote', 'granola'); ?></button>
                <button type="submit" class="g-button g-button--secondary" name="quote_intent" value="download"><?php esc_html_e('Download Quote (PDF)', 'granola'); ?></button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="cart-collaterals">
    <?php
        /**
         * Cart collaterals hook.
         *
         * @hooked woocommerce_cross_sell_display
         * @hooked woocommerce_cart_totals - 10
         */
        do_action('woocommerce_cart_collaterals');
    ?>
</div>

<?php do_action('woocommerce_after_cart'); ?>

<?php if ($show_quote_share) : ?>
<script>
    (function () {
        const modal = document.querySelector('[data-quote-share-modal]');
        if (!modal) {
            return;
        }

        const closeModal = function () {
            modal.setAttribute('hidden', 'hidden');
            modal.setAttribute('aria-hidden', 'true');
        };

        const openModal = function () {
            modal.removeAttribute('hidden');
            modal.setAttribute('aria-hidden', 'false');
        };

        document.addEventListener('click', function (event) {
            const openTrigger = event.target.closest('[data-quote-share-open]');

            if (openTrigger) {
                event.preventDefault();
                openModal();
                return;
            }

            const closeTrigger = event.target.closest('[data-quote-share-close]');
            if (closeTrigger && modal.contains(closeTrigger)) {
                event.preventDefault();
                closeModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hasAttribute('hidden')) {
                closeModal();
            }
        });
    })();
</script>
<?php endif; ?>
