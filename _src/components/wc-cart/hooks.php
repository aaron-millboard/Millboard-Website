<?php

namespace Granola\Components\WC_Cart;

\add_filter('granola/component/wc-cart', __NAMESPACE__ . '\\filter_args');

\remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 10);
\remove_action('woocommerce_cart_collaterals', 'woocommerce_cart_totals', 10);
