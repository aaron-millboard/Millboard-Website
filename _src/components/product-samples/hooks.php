<?php

namespace Granola\Components\ProductSamples;

\add_filter('granola/component/product-samples', __NAMESPACE__ . '\\filter_args');

\add_filter('woocommerce_add_to_cart_validation', __NAMESPACE__ . '\\sample_product_add_to_cart_validation', 10, 3);
