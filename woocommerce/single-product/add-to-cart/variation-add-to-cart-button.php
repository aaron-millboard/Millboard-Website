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
$show_calculator = \get_field('enable_calculator', $product->get_id());
$stock_quantity = $product->get_stock_quantity();

// Bail early - stock set to 0, don't allow "add to cart" functionality for main product.
if (empty($stock_quantity) && !is_null($stock_quantity)) {
    return;
}

// Get default product
$default_product_in_stock = \Theme\WooCommerce\Utils::is_default_product_variant_in_stock($product);

?>
<div class="product__toggles variations_button">
    <?php woocommerce_quantity_input([
        'min_value'   => $product->get_min_purchase_quantity(),
        'max_value'   => $product->get_max_purchase_quantity(),
        'input_value' => isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
    ]); ?>

    <?php if ($show_calculator) {
        echo \Granola\Component::get('product-calculator/cta', ['product' => $product]);
    } ?>
</div>

<?php if ($show_calculator) {
    echo \Granola\Component::get('product-calculator');
} ?>

<?php if (!empty($default_product_in_stock)) { ?>
    <div class="product__add-to-cart-wrapper">
        <?php woocommerce_single_variation(); // render price ?>

        <button type="submit" class="single_add_to_cart_button button alt<?= esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"><?= esc_html($product->single_add_to_cart_text()); ?></button>

        <input type="hidden" name="add-to-cart" value="<?= absint($product->get_id()); ?>" />
        <input type="hidden" name="product_id" value="<?= absint($product->get_id()); ?>" />
        <input type="hidden" name="variation_id" class="variation_id" value="0" />
    </div>
<?php } ?>
