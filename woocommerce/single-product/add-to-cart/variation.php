<?php

/**
 * Single variation display
 *
 * This is a javascript-based template for single variations (see https://codex.wordpress.org/Javascript_Reference/wp.template).
 * The values will be dynamically replaced after selecting attributes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined('ABSPATH') || exit;

// Resolve tax global status
if (function_exists('wc_prices_include_tax') && wc_prices_include_tax()) {
    $args['tax_included'] = true;
} else {
    $args['tax_included'] = false;
}

?>
<script type="text/template" id="tmpl-variation-template">
    <div class="woocommerce-variation-price">{{{ data.variation.price_html }}}</div>
    <div class="woocommerce-variation-tax">
        <?php echo $args['tax_included'] ? esc_html__('Incl VAT', 'granola') : esc_html__('Excl VAT', 'granola'); ?>
    </div>
</script>
<script type="text/template" id="tmpl-unavailable-variation-template">
    <p role="alert"><?php esc_html_e('Sorry, this product is unavailable. Please choose a different combination.', 'granola'); ?></p>
</script>
