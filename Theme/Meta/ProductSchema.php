<?php

namespace Theme\Meta;

/**
 * Stops product variations inheriting their parent's GTIN in Yoast's product schema.
 *
 * Yoast WooCommerce SEO describes a variable product as a ProductGroup whose
 * hasVariant entries each take the variation's own global identifiers, or fall
 * back to the parent's when the variation has none. For a board that fallback
 * is wrong on the samples: the large and small samples are different trade
 * items with their own SKUs and no barcode, yet every one was published with
 * the full board's GTIN. That contradicts the Merchant Center feeds, which
 * list the samples with no GTIN.
 *
 * So a variant keeps an identifier only when the variation holds it itself, or
 * when the variant is the parent's own trade item: the full board carries the
 * parent's SKU, so the parent's barcode is genuinely its barcode.
 */
class ProductSchema
{
    private const IDENTIFIERS = ['gtin8', 'gtin12', 'gtin13', 'gtin14', 'mpn'];

    public static function init(): void
    {
        \add_filter('wpseo_schema_product', [self::class, 'drop_inherited_variant_identifiers'], 20);
    }

    /**
     * @param mixed $data The Yoast Product (or ProductGroup) schema piece.
     *
     * @return mixed
     */
    public static function drop_inherited_variant_identifiers($data)
    {
        if (!is_array($data) || empty($data['hasVariant']) || !is_array($data['hasVariant'])) {
            return $data;
        }

        $product = \function_exists('wc_get_product') ? \wc_get_product(\get_queried_object_id()) : null;
        if (!$product || !$product->is_type('variable')) {
            return $data;
        }

        $parent_sku = (string) $product->get_sku();
        $variations_by_sku = [];
        foreach ($product->get_available_variations('objects') as $variation) {
            $variations_by_sku[(string) $variation->get_sku()] = $variation;
        }

        foreach ($data['hasVariant'] as $i => $variant) {
            $sku = (string) ($variant['sku'] ?? '');
            if ($sku === '' || $sku === $parent_sku || !isset($variations_by_sku[$sku])) {
                continue;
            }

            $own = \get_post_meta($variations_by_sku[$sku]->get_id(), 'wpseo_variation_global_identifiers_values', true);
            foreach (self::IDENTIFIERS as $key) {
                if (isset($variant[$key]) && empty($own[$key])) {
                    unset($data['hasVariant'][$i][$key]);
                }
            }
        }

        return $data;
    }
}
