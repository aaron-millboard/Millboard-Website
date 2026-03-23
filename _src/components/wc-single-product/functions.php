<?php

namespace Granola\Components\WC_SingleProduct;

function get_pricing_description(?\WC_Product $product = null): string
{
    if (empty($product) || ! $product instanceof \WC_Product) {
        return '';
    }

    $hide_pricing_description = \get_field('hide_pricing_description', $product->get_id());
    if ((string) $hide_pricing_description === '1') {
        return '';
    }

    $pricing_description_override = \get_field('pricing_description_override', $product->get_id());
    if (!empty($pricing_description_override)) {
        return (string) $pricing_description_override;
    }

    $pricing_description = \get_field('cpt_product_pricing_description', 'options');
    if (!empty($pricing_description)) {
        return (string) $pricing_description;
    }

    if (function_exists('wc_prices_include_tax') && wc_prices_include_tax()) {
        return __('Incl VAT', 'granola');
    }

    return __('Excl VAT', 'granola');
}

function filter_args(array $args): ?array
{
    global $product;

    if (empty($product) || ! $product instanceof \WC_Product) {
        return [];
    }

    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'id' => 'product',
        'classes' => [],
        'preheading' => '',
        'heading' => '',
        'description' => '',
        'header_cta' => [],
        'colour_variations' => [],
        'width_variations' => [],
        'board_width_variations' => [],
        'samples' => [],
    ], $args);

    // -------------------------------------------------------------------------
    // 1. Required classes and ID
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'wp-block',
        'woocommerce',
    ], \wc_get_product_class('', $product));

    $args['id'] = "product-{$product->get_id()}";

    // -------------------------------------------------------------------------
    // 2. Retrieve product data
    // -------------------------------------------------------------------------

    // Preheading - get name of first category
    $categories = \wp_get_post_terms($product->get_id(), 'product_cat');
    if (!is_wp_error($categories) && ! empty($categories)) {
        // Get primary category if set
        if (function_exists('yoast_get_primary_term_id')) {
            $primary_category_id = yoast_get_primary_term_id('product_cat', $product->get_id());
        } else {
            $primary_category_id = false;
        }
        // If no primary category set or yoast is not active
        if (!$primary_category_id) {
            // otherwise fallback to first category
            $primary_category_id = $categories[0]->term_id;
        }

        // get parent category if primary is a child in a loop to get top level category
        $category_for_preheading = \get_term($primary_category_id);
        do {
            // Get parent category
            $parent_category = \get_term($category_for_preheading->parent);

            // If parent category exists and is not an error, set it as primary category for next loop iteration
            if ($parent_category && !is_wp_error($parent_category)) {
                $category_for_preheading = $parent_category;
            } else {
                break;
            }
        } while ($category_for_preheading->parent != 0);

        $args['preheading'] = $category_for_preheading->name;
    }

    // Heading - get product title
    $args['heading'] = $product->get_name();

    // Description - get product short description
    $args['description'] = $product->get_short_description();

    // CTA under description
    $args['header_cta'] = \get_field('header_cta', $product->get_id());
    if (!empty($args['header_cta']['title'])) {
        $args['header_cta']['content'] = \Granola\SVG::get('icons-custom/pencil.svg') . $args['header_cta']['title'];
    }

    // -------------------------------------------------------------------------
    // 3. Variations (to be used in the variation selector component)
    // -------------------------------------------------------------------------

    $protected_variations = ['colour', 'board_width', 'width'];
    $args['selectors'] = [];
    foreach ($protected_variations as $protected_variation) {
        $repeater_data = $args[$protected_variation . '_variations'];
        // Skip if corresponding repeater is empty
        if (empty($repeater_data)) {
            continue;
        }

        // get attribute label
        $attr_slug = 'pa_' . $protected_variation;
        $label = wc_attribute_label($attr_slug);
        // Try to repeat with hyphen if slug returned
        if ($label === $attr_slug) {
            $label = wc_attribute_label('pa_' . str_replace('_', '-', $protected_variation));
        }
        // If still slug just replace underscores with spaces
        if ($label === $attr_slug) {
            $label = str_replace('_', ' ', $protected_variation);
        }

        $args['selectors'][] = [
            'variation' => $protected_variation,
            'heading' => sprintf(
                // translators: product attribute name, e.g. "Colour".
                __('Select %s:', 'granola'),
                strtolower($label)
            ),
            'variations' => $repeater_data,
        ];
    }

    // -------------------------------------------------------------------------
    // 4. Locale attribute for price formatting and tax rate for calculator
    // -------------------------------------------------------------------------
    $currency_code = \get_woocommerce_currency();
    if ($currency_code) {
        $args['attributes']['data-currency-code'] = $currency_code;
    }

    // Get country intl code
    $locale = \get_locale();
    if ($locale) {
        $args['attributes']['data-locale'] = $locale; // e.g. en_GB
    }

    // -------------------------------------------------------------------------
    // 5. Price attribute for calculator
    // -------------------------------------------------------------------------
    if ($product->is_type('variable')) {
        $default_variation_id = \Theme\Utils\Woocommerce::get_default_variation_id($product);
        if ($default_variation_id) {
            $variation = \wc_get_product($default_variation_id);
            $price = $variation->get_price();
        } else {
            $price = $product->get_price();
        }
    } else {
        $price = $product->get_price();
    }

    $args['attributes']['data-price'] = $price;

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

// Add radio buttons for variations instead of dropdowns
function render_radio_variations($html, $args)
{
    if (!$args['options'] || !$args['product']) {
        return $html;
    }

    $html .= \Granola\Component::get('wc-single-product/variation', $args);

    return $html;
}
