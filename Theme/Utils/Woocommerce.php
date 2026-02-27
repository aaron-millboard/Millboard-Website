<?php

namespace Theme\Utils;

class Woocommerce
{
    /**
    * Get the default variation ID for a variable product.
    *
    * @param WC_Product $product The variable product object.
    * @return int|false The default variation ID or false if not found.
    */
    public static function get_default_variation_id($product)
    {

        // Check if the product is variable and has a default variation set.
        if (!$product->is_type('variable') || !$product->get_default_attributes()) {
            return $product->get_id(); // Return the product ID for non-variable products or if no default variation is set.
        }
        $attributes = $product->get_default_attributes();

        foreach ($attributes as $key => $value) {
            $attributes[ 'attribute_' . $key ] = $value;
            unset($attributes[ $key ]);
        }

        $data_store = \WC_Data_Store::load('product');
        return $data_store->find_matching_product_variation($product, $attributes);
    }
}
