<?php

namespace Granola\Components\WC_OrderEssentials;

function filter_args(array $args): ?array
{
    return $args;
}

function get_cart_step_context(): array
{
    $context = \Theme\WooCommerce\OrderEssentials::get_cart_step_context();
    return is_array($context) ? $context : [];
}

function resolve_unit_price(int $product_id): float
{
    $product = \wc_get_product($product_id);

    if (!$product instanceof \WC_Product) {
        return 0.0;
    }

    if ($product instanceof \WC_Product_Variable) {
        $default_attributes = $product->get_default_attributes();

        if (!empty($default_attributes)) {
            foreach ($product->get_children() as $variation_id) {
                $variation = \wc_get_product($variation_id);

                if (!$variation instanceof \WC_Product_Variation) {
                    continue;
                }

                $matches_default_attributes = true;

                foreach ($default_attributes as $attribute_name => $attribute_value) {
                    if ((string) $variation->get_attribute($attribute_name) !== (string) $attribute_value) {
                        $matches_default_attributes = false;
                        break;
                    }
                }

                if ($matches_default_attributes) {
                    return (float) \wc_get_price_to_display($variation);
                }
            }
        }

        foreach ($product->get_children() as $variation_id) {
            $variation = \wc_get_product($variation_id);

            if (!$variation instanceof \WC_Product_Variation || !$variation->is_purchasable()) {
                continue;
            }

            $variation_price = (float) \wc_get_price_to_display($variation);

            if ($variation_price > 0) {
                return $variation_price;
            }
        }
    }

    return (float) \wc_get_price_to_display($product);
}

function render_before_cart(): void
{
    \do_action('woocommerce_before_cart');
}

function render_after_cart(): void
{
    \do_action('woocommerce_after_cart');
}
