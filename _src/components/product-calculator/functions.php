<?php

namespace Granola\Components\ProductCalculator;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'attributes' => [],
        'product' => wc_get_product(),
        'classes' => [],
        'title' => __('Calculator', 'granola'),
        'description' => __('Use our handy tool to calculate the amount of m² you need to complete your Millboard project.', 'granola'),
        'incl_tax_label' => __('* Prices inclusive of VAT', 'granola'),
        'excl_tax_label' => __('* Prices exclusive of VAT', 'granola'),
        'tax_toggle_label' => __('Show price inclusive of VAT', 'granola'),
        'disclaimer' => __('This is only a guide board price and does not allow for fixings, subframe accessories or installation.', 'granola'),
    ], $args);

    // Required classes.
    $args['classes'] = array_merge([
        'product-calculator',
    ], $args['classes']);

    // Hide calculator by default, it will be shown when the user clicks the "Calculate" button.
    $args['attributes']['style'] = 'display: none;';

    // Add required dataset.
    if (!empty($args['product']) && $args['product'] instanceof \WC_Product) {
        // Get shipping dimensions.
        $length = $args['product']->get_length() ?: 0;
        $width = $args['product']->get_width() ?: 0;

        // Manually adjust width by 4mm
        $width = $width + 4;

        $area = $length * $width / 1000000; // Convert from mm2 to m2.

        // Cap to 2 decimal.
        $area = round($area, 2);

        // Add dataset for board area
        $args['attributes']['data-board-area'] = $area;

        // Add dataset for price, we will use it in the calculator script to calculate the total price based on the area.
        // check if variable
        if ($args['product']->is_type('variable')) {
            $default_variation_id = \Theme\Utils\Woocommerce::get_default_variation_id($args['product']);
            if ($default_variation_id) {
                $variation = \wc_get_product($default_variation_id);
                $price = $variation->get_price();
            } else {
                $price = $args['product']->get_price();
            }
        } else {
            $price = $args['product']->get_price();
        }

        $args['attributes']['data-price'] = $price;

        // Add dataset for currency symbol
        $currency_code = \get_woocommerce_currency();
        if ($currency_code) {
            $args['attributes']['data-currency-code'] = $currency_code;
        }

        // Get country intl code
        $locale = \get_locale();
        if ($locale) {
            $args['attributes']['data-locale'] = $locale; // e.g. en_GB
        }
    }

    // Resolve tax global status
    if (function_exists('wc_prices_include_tax') && wc_prices_include_tax()) {
        $args['attributes']['data-tax-included'] = 'true'; // for js script
        $args['tax_included'] = true;
    } else {
        $args['attributes']['data-tax-included'] = 'false'; // for js script
        $args['tax_included'] = false;
    }

    // Resolve tax rate
    // https://stackoverflow.com/questions/57862170/how-can-i-get-all-available-tax-rates-in-woocommerce
    $all_tax_rates = [];
    $tax_classes = \WC_Tax::get_tax_classes(); // Retrieve all tax classes.
    if (!in_array('', $tax_classes)) { // Make sure "Standard rate" (empty class name) is present.
        array_unshift($tax_classes, '');
    }
    foreach ($tax_classes as $tax_class) { // For each tax class, get all rates.
        $taxes = \WC_Tax::get_rates_for_tax_class($tax_class);
        $all_tax_rates = array_merge($all_tax_rates, $taxes);
    }
    // Get the first tax rate
    $first_tax_rate = reset($all_tax_rates);
    if (!empty($first_tax_rate) && isset($first_tax_rate->tax_rate)) {
        // Convert to array and get the rate percentage
        $rate = $first_tax_rate->tax_rate ?? 0;
        $args['attributes']['data-tax-rate'] = $rate; // for js script
        $args['tax_rate'] = $rate;
    } else {
        $args['attributes']['data-tax-rate'] = 0; // for js script
        $args['tax_rate'] = 0;
    }

    // Process labels (fetch from ACF options)
    $title = get_field('product_calculator_title', 'options');
    if ($title) {
        $args['title'] = $title;
    }
    $description = get_field('product_calculator_description', 'options');
    if ($description) {
        $args['description'] = $description;
    }
    $incl_tax_label = get_field('product_calculator_price_incl_tax_msg', 'options');
    if ($incl_tax_label) {
        $args['incl_tax_label'] = $incl_tax_label;
    }
    $excl_tax_label = get_field('product_calculator_price_excl_tax_msg', 'options');
    if ($excl_tax_label) {
        $args['excl_tax_label'] = $excl_tax_label;
    }
    $tax_toggle_label = get_field('product_calculator_toggle_tax_msg', 'options');
    if ($tax_toggle_label) {
        $args['tax_toggle_label'] = $tax_toggle_label;
    }
    $disclaimer = get_field('product_calculator_disclaimer', 'options');
    if ($disclaimer) {
        $args['disclaimer'] = $disclaimer;
    }


    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
