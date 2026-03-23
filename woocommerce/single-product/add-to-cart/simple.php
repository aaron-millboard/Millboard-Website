<?php

/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.2.0
 */

defined('ABSPATH') || exit;

global $product;

// CTA under description
$show_calculator = \get_field('enable_calculator', $product->get_id());
$pricing_description = \Granola\Components\WC_SingleProduct\get_pricing_description($product);

if (! $product->is_purchasable()) {
    return;
}

// Don't show 'out of stock' message.
// echo wc_get_stock_html($product); // WPCS: XSS ok.

if ($product->is_in_stock()) : ?>
    <form class="cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype='multipart/form-data'>

        <div class="product__toggles">

            <?php
                woocommerce_quantity_input(
                    array(
                        'min_value'   => $product->get_min_purchase_quantity(),
                        'max_value'   => $product->get_max_purchase_quantity(),
                        'input_value' => isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
                    )
                );
            ?>

            <?php if ($show_calculator) {
                echo \Granola\Component::get('product-calculator/cta', ['product' => $product]);
            } ?>

        </div>

        <?php if ($show_calculator) {
            echo \Granola\Component::get('product-calculator');
        } ?>

        <div class="product__add-to-cart-wrapper">
            <div class="woocommerce-simple">
                <div class="woocommerce-simple-price">
                    <?= $product->get_price_html(); ?>
                </div>
            </div>

            <?php if (!empty($pricing_description)) { ?>
                <div class="product__pricing-description woocommerce-simple-tax">
                    <?= wp_kses_post($pricing_description); ?>
                </div>
            <?php } ?>

            <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"><?php echo esc_html($product->single_add_to_cart_text()); ?></button>
        </div>

    </form>
<?php endif; ?>
