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
$calculator_cta_heading = '';
$categories = wp_get_post_terms($product->get_id(), 'product_cat');

if (!is_wp_error($categories) && ! empty($categories)) {
    $calculator_cta_heading = $categories[0]->name . ' Calculator';
}

?>
<div class="product__toggles variations_button">
    <?php woocommerce_quantity_input([
        'min_value'   => $product->get_min_purchase_quantity(),
        'max_value'   => $product->get_max_purchase_quantity(),
        'input_value' => isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
    ]); ?>

    <?php if ($show_calculator) { ?>
        <a href="#" class="product__calculator-cta">
            <?= \Granola\SVG::get('icons-custom/calculator.svg'); ?>
            <div class="product__calculator-cta--content">
                <?php if (!empty($calculator_cta_heading)) { ?>
                    <div class="product__calculator-cta-heading">
                        <?= esc_html($calculator_cta_heading); ?>
                    </div>
                <?php } ?>

                <span class="product__calculator-cta-description">
                    <?= esc_html__('How much do I need?', 'granola'); ?>
                </span>
            </div>
        </a>
    <?php } ?>
</div>


<div class="product__calculator" style="display: none;">
    <div class="product__calculator__header">
        <h3 class="product__calculator__header--title">
            <?= __('Decking Calculator', 'granola'); ?>
        </h3>

        <a href="#" class="product__calculator__header--close" aria-label="<?= esc_attr__('Close calculator', 'granola'); ?>">
            <?= esc_html__('Close', 'granola'); ?>
            <?= \Granola\SVG::get('icons/cross.svg'); ?>
        </a>
    </div>

    <div class="product__calculator__body">
        <!-- Section 1 - Inputs -->
        <div class="product__calculator__section product__calculator__section--inputs">
            <div class="product__calculator__section__content">
                <div class="product__calculator__unit-selection">
                    <div class="product__calculator--label">
                        <?= esc_html__('Select unit:', 'granola'); ?>
                    </div>

                    <div class="product__calculator__radios">
                        <div class="product__calculator--radio">
                            <input id="calculator_unit_meters" type="radio" name="calculator_unit" value="meters" checked>
                            <label for="calculator_unit_meters"><?= __('Meters (m2)', 'granola'); ?></label>
                        </div>

                        <div class="product__calculator--radio">
                            <input id="calculator_unit_feet" type="radio" name="calculator_unit" value="feet">
                            <label for="calculator_unit_feet"><?= __('Feet (ft2)', 'granola'); ?></label>
                        </div>
                    </div>
                </div>

                <div class="product__calculator-input-wrapper">
                    <?php woocommerce_quantity_input([
                        'min_value'   => 1,
                        'max_value'   => 1000,
                        'input_value' => 1,
                        'name'        => 'calculator_length',
                        'class'       => 'product__calculator-input',
                        'show_extra_info' => false,
                    ]); ?>
                </div>
            </div>

            <div class="product__calculator-wastage">
                <label class="product__calculator-checkbox">
                    <input class="quantity-wastage-checkbox" type="checkbox" name="calculator_wastage">
                    <span><?= esc_html__('Add 10% for wastage', 'granola'); ?></span>
                </label>
            </div>
        </div>

        <div class="product__calculator__section product__calculator__section--result">
            <div class="product__calculator__section__content">
                <span class="product__calculator-result-label"><?= esc_html__('Total Square Meters', 'granola'); ?></span>
                <span class="product__calculator-result-value" data-result="area">0.00</span>
            </div>
        </div>

        <div class="product__calculator__section product__calculator__section--total">
            <div class="product__calculator__section__content">
                <span class="product__calculator-result-label"><?= esc_html__('Total Price', 'granola'); ?></span>
                <span class="product__calculator-result-value" data-result="price">£0.00</span>
            </div>
        </div>

        <div class="product__calculator__tax-notice">
            <span><?= esc_html__('* Prices exclusive of VAT', 'granola'); ?></span>
        </div>

        <div class="product__calculator__notice">
            <div class="product__calculator__notice--icon">
                <?= \Granola\SVG::get('icons/warning.svg'); ?>
            </div>
            <span class="product__calculator__notice--text">
                <?= esc_html__('This is only a guide board price and does not allow for fixings, subframe accessories or installation.', 'granola'); ?>
            </span>
        </div>

    </div>

</div>

<div class="product__add-to-cart-wrapper">
    <?php woocommerce_single_variation(); // render price ?>

    <button type="submit" class="single_add_to_cart_button button alt<?= esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"><?= esc_html($product->single_add_to_cart_text()); ?></button>

    <input type="hidden" name="add-to-cart" value="<?= absint($product->get_id()); ?>" />
    <input type="hidden" name="product_id" value="<?= absint($product->get_id()); ?>" />
    <input type="hidden" name="variation_id" class="variation_id" value="0" />
</div>
