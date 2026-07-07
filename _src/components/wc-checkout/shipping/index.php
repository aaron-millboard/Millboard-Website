<div class="checkout__section-shipping">
    <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
        <?php do_action('woocommerce_review_order_before_shipping'); ?>

        <?php wc_cart_totals_shipping_html(); ?>

        <?php do_action('woocommerce_review_order_after_shipping'); ?>
    <?php endif; ?>
</div>
