<?php

/**
 * Single variation cart button
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.2.0
 */

defined('ABSPATH') || exit;

global $product;


// CTA under description
$header_cta = \get_field('header_cta', $product->get_id());
$header_cta['content'] = \Granola\SVG::get('icons-custom/calculator.svg') . "How much do I need?";

// get name of first category
$header_cta_heading = '';
$categories = wp_get_post_terms($product->get_id(), 'product_cat');
if (!is_wp_error($categories) && ! empty($categories)) {
    $header_cta_heading = $categories[0]->name . ' Calculator';
}


?>


<div class="product__toggles variations_button">

    <?php
    woocommerce_quantity_input(
        array(
            'min_value'   => $product->get_min_purchase_quantity(),
            'max_value'   => $product->get_max_purchase_quantity(),
            'input_value' => isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
        )
    );
    ?>
    
    <?php if (!empty($header_cta)) { ?>
        <div class="product__calculator-cta">
            <?php if (!empty($header_cta_heading)) { ?>
                <div class="product__calculator-cta-heading">
                    <?php echo esc_html($header_cta_heading); ?>
                </div>
            <?php } ?>
            <?= \Granola\Component::get('link', $header_cta); ?>
        </div>
    <?php } ?>

</div>

<div class="product__add-to-cart-wrapper">

    <?php
        // render price
        woocommerce_single_variation();
    ?>

    <button type="submit" class="single_add_to_cart_button button alt<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"><?php echo esc_html($product->single_add_to_cart_text()); ?></button>

    <input type="hidden" name="add-to-cart" value="<?php echo absint($product->get_id()); ?>" />
    <input type="hidden" name="product_id" value="<?php echo absint($product->get_id()); ?>" />
    <input type="hidden" name="variation_id" class="variation_id" value="0" />

</div>