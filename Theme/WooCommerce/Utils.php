<?php

namespace Theme\WooCommerce;

class Utils
{
    /**
     * Retrieves the default variation of a product, if one exists.
     *
     * @param \WC_Product|null $product A product object to check against.
     * @return \WC_Product|null The default variation product, if one exists. Null otherwise.
     */
    public static function get_default_product_variant(?\WC_Product $product): ?\WC_Product
    {
        // Bail early - invalid product passed.
        if (empty($product)) {
            return null;
        }

        // Bail early - product is not the base variable product and not a variation.
        if (!$product->is_type('variable') && !$product->is_type('variation')) {
            return null;
        }

        if ($product->is_type('variation')) {
            $product = \wc_get_product($product->get_parent_id());
        }

        /** @var \WC_Product_Variable $product */
        $product_variations = $product->get_available_variations('objects');

        if (empty($product_variations)) {
            return null;
        }

        $default_attributes = $product->get_default_attributes();

        $default_product = array_find($product_variations, function ($variation) use ($default_attributes) {
            return $variation->get_attributes() === $default_attributes;
        });

        return $default_product ?? null;
    }

    /**
     * Determine whether a product has a variation that is in stock.
     *
     * @param \WC_Product|null $product A product object to check against.
     * @return boolean|null Whether a product has a variation that is in stock. False for out of stock. Null for no default found.
     */
    public static function is_default_product_variant_in_stock(?\WC_Product $product): ?bool
    {
        $default_product = self::get_default_product_variant($product);

        // Return null - no product found.
        if ($default_product === null) {
            return null;
        }

        // Return bool - if variation exists, is it in stock?
        return $default_product->get_stock_status() === 'instock';
    }

    /**
     * Determine whether a product is a variation's default variant.
     *
     * @param \WC_Product|null $product A product object to check against.
     * @return boolean|null Whether the given product is a default variation.
     */
    public static function is_default_product(?\WC_Product $product): ?bool
    {
        $default_product = self::get_default_product_variant($product);

        // Return null - no product found.
        if ($default_product === null) {
            return null;
        }

        // Return bool - if variation exists, is it the same as the passed product? OR is this the base product?
        return $product->get_id() === $default_product->get_id() || $product->get_type() === 'product';
    }

    /**
     * Determine whether a product is sample product.
     *
     * In this case, a sample product is a variation product that isn't the default variant.
     *
     * @param \WC_Product|null $product A product object to check against.
     * @return boolean|null Whether the given product is a sample product.
     */
    public static function is_sample(?\WC_Product $product): ?bool
    {
        if (empty($product)) {
            return false;
        }

        return !self::is_default_product($product);
    }

    /**
     * Determine whether a product is a free sample product.
     *
     * In this case, a free sample product is a variation product that isn't the default variant AND has a price of 0.
     *
     * @param \WC_Product|null $product A product object to check against.
     * @return boolean|null Whether the given product is a free sample product.
     */
    public static function is_free_sample(?\WC_Product $product): ?bool
    {
        if (empty($product)) {
            return false;
        }

        return self::is_sample($product) && empty($product->get_price());
    }

    /**
     * Generate array of product attributes that can be displayed on the Cart, Checkout, and Thankyou page.
     *
     * @param \WC_Product $product
     * @return array An array of attribute data.
     */
    public static function get_product_display_attributes(\WC_Product $product): array
    {
        $attributes = [];

        // Bail early - product is not the base variable product and not a variation.
        if (!$product->is_type('variable') && !$product->is_type('variation')) {
            return $attributes;
        }

        // Retrieve prodiuct color data first.
        $colour_taxonomy_name = \get_field('product_colour_taxonomy', 'options');
        $colour_attribute_name = ltrim($colour_taxonomy_name, 'pa_');
        $colour_attribute_value = $product->get_attribute($colour_taxonomy_name ?? 'pa_colour');

        if (!empty($colour_attribute_value)) {
            $attributes[$colour_taxonomy_name] = [
                'name' => $colour_attribute_name,
                'value' => $colour_attribute_value,
            ];
        }

        // Retrieve board width data.
        $board_width_taxonomy_name = \get_field('product_board_width_taxonomy', 'options');
        $board_width_attribute_name = ltrim($board_width_taxonomy_name, 'pa_');
        $board_width_attribute_value = $product->get_attribute($board_width_taxonomy_name ?? 'pa_board-width');

        if (!empty($board_width_attribute_value)) {
            $attributes[$board_width_taxonomy_name] = [
                'name' => $board_width_attribute_name,
                'value' => $board_width_attribute_value,
            ];
        }

        return $attributes;
    }
}
