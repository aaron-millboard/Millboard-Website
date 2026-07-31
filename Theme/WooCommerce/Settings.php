<?php

namespace Theme\WooCommerce;

class Settings
{
    public static function init()
    {
        \add_action('acf/init', [__CLASS__, 'add_product_options_pages']);
        \add_action('acf/init', [__CLASS__, 'register_acf_fields']);
    }

    public static function add_product_options_pages(): void
    {
        if (!function_exists('acf_add_options_sub_page')) {
            return;
        }

        \acf_add_options_sub_page([
            'page_title'  => \__('Product Settings', 'granola'),
            'menu_title'  => \__('Product Settings', 'granola'),
            'menu_slug'   => 'acf-options-product-settings',
            'parent_slug' => 'edit.php?post_type=product',
        ]);
    }

    public static function register_acf_fields(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        \acf_add_local_field_group([
            'key' => 'group_product_settings_free_samples',
            'title' => 'Free Samples',
            'fields' => [
                [
                    'key' => 'field_free_sample_disallowed_countries',
                    'label' => 'Disallowed countries',
                    'name' => 'free_sample_disallowed_countries',
                    'type' => 'select',
                    'instructions' => 'Selected countries will be removed from the allowed list for free samples.',
                    'multiple' => 1,
                    'ui' => 1,
                    'return_format' => 'value',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'acf-options-product-settings',
                    ],
                ],
            ],
        ]);

        \acf_add_local_field_group([
            'key' => 'group_product_settings_order_essentials',
            'title' => 'Order Essentials',
            'fields' => [
                [
                    'key' => 'field_order_essentials_default_project_type',
                    'label' => 'Default project type',
                    'name' => 'order_essentials_default_project_type',
                    'type' => 'select',
                    'instructions' => 'Select the default project type used for recommendations.',
                    'choices' => [
                        'residential' => 'Residential',
                        'commercial' => 'Commercial',
                    ],
                    'default_value' => 'residential',
                    'ui' => 1,
                    'return_format' => 'value',
                ],
                [
                    'key' => 'field_order_essentials_waste_percent',
                    'label' => 'Waste allowance (%)',
                    'name' => 'order_essentials_waste_percent',
                    'type' => 'number',
                    'instructions' => 'Applied only to recommendations with "Apply waste allowance" ticked, matching the internal calculator (default 10%).',
                    'default_value' => 10,
                    'min' => 0,
                    'max' => 100,
                    'step' => 0.5,
                    'append' => '%',
                ],
                [
                    'key' => 'field_order_essentials_recommendation_sources',
                    'label' => 'Recommendation sources',
                    'name' => 'order_essentials_recommendation_sources',
                    'type' => 'repeater',
                    'instructions' => 'Add each product or category that should trigger recommended essentials.',
                    'layout' => 'block',
                    'button_label' => 'Add source',
                    'min' => 0,
                    'max' => 0,
                    'collapsed' => 'field_order_essentials_source_product_ids',
                    'sub_fields' => [
                        [
                            'key' => 'field_order_essentials_source_product_ids',
                            'label' => 'Source products',
                            'name' => 'source_product_ids',
                            'type' => 'post_object',
                            'instructions' => 'Products that should trigger these recommendations.',
                            'post_type' => [
                                'product',
                            ],
                            'post_status' => [
                                'publish',
                            ],
                            'taxonomy' => '',
                            'return_format' => 'id',
                            'multiple' => 1,
                            'allow_null' => 1,
                            'ui' => 1,
                        ],
                        [
                            'key' => 'field_order_essentials_source_category_slugs',
                            'label' => 'Source categories',
                            'name' => 'source_category_slugs',
                            'type' => 'taxonomy',
                            'instructions' => 'Product categories that should trigger these recommendations.',
                            'taxonomy' => 'product_cat',
                            'field_type' => 'multi_select',
                            'return_format' => 'object',
                            'add_term' => 0,
                            'save_terms' => 0,
                            'load_terms' => 0,
                            'allow_null' => 1,
                            'multiple' => 1,
                        ],
                        [
                            'key' => 'field_order_essentials_requires_category_slugs',
                            'label' => 'Only if basket also contains',
                            'name' => 'requires_category_slugs',
                            'type' => 'taxonomy',
                            'instructions' => 'Optional. These recommendations apply only when the basket also contains a product from one of these categories, e.g. the DuoSpan fascia rate.',
                            'taxonomy' => 'product_cat',
                            'field_type' => 'multi_select',
                            'return_format' => 'object',
                            'add_term' => 0,
                            'save_terms' => 0,
                            'load_terms' => 0,
                            'allow_null' => 1,
                            'multiple' => 1,
                        ],
                        [
                            'key' => 'field_order_essentials_excludes_category_slugs',
                            'label' => 'Skip if basket contains',
                            'name' => 'excludes_category_slugs',
                            'type' => 'taxonomy',
                            'instructions' => 'Optional. These recommendations are skipped when the basket contains a product from one of these categories, e.g. the DuoFix kit is not offered when 126mm accent boards are ordered.',
                            'taxonomy' => 'product_cat',
                            'field_type' => 'multi_select',
                            'return_format' => 'object',
                            'add_term' => 0,
                            'save_terms' => 0,
                            'load_terms' => 0,
                            'allow_null' => 1,
                            'multiple' => 1,
                        ],
                        [
                            'key' => 'field_order_essentials_recommendations',
                            'label' => 'Recommended products',
                            'name' => 'recommendations',
                            'type' => 'repeater',
                            'instructions' => 'Products and quantities recommended when the source matches the basket.',
                            'layout' => 'table',
                            'button_label' => 'Add recommendation',
                            'min' => 0,
                            'max' => 0,
                            'collapsed' => 'field_order_essentials_target_product_id',
                            'sub_fields' => [
                                [
                                    'key' => 'field_order_essentials_target_product_id',
                                    'label' => 'Recommended product',
                                    'name' => 'target_product_id',
                                    'type' => 'post_object',
                                    'post_type' => [
                                        'product',
                                    ],
                                    'post_status' => [
                                        'publish',
                                    ],
                                    'taxonomy' => '',
                                    'return_format' => 'id',
                                    'multiple' => 0,
                                    'allow_null' => 0,
                                    'ui' => 1,
                                ],
                                [
                                    'key' => 'field_order_essentials_residential_multiplier',
                                    'label' => 'Quantity for Residential',
                                    'name' => 'residential_multiplier',
                                    'type' => 'number',
                                    'instructions' => 'Recommended quantity per matching source item in the basket.',
                                    'min' => 0,
                                    'step' => 0.001,
                                ],
                                [
                                    'key' => 'field_order_essentials_commercial_multiplier',
                                    'label' => 'Quantity for Commercial',
                                    'name' => 'commercial_multiplier',
                                    'type' => 'number',
                                    'instructions' => 'Recommended quantity per matching source item in the basket.',
                                    'min' => 0,
                                    'step' => 0.001,
                                ],
                                [
                                    'key' => 'field_order_essentials_basis',
                                    'label' => 'Quantity is',
                                    'name' => 'basis',
                                    'type' => 'select',
                                    'instructions' => 'Per item: multiplied by the quantity of the source in the basket. Per m2: multiplied by the project area worked out from the boards in the basket. Per project: a fixed quantity once per order.',
                                    'choices' => [
                                        'per_unit' => 'Per source item',
                                        'per_sqm' => 'Per m² of project area',
                                        'per_project' => 'Per project (fixed)',
                                    ],
                                    'default_value' => 'per_unit',
                                    'ui' => 1,
                                    'return_format' => 'value',
                                    'allow_null' => 0,
                                ],
                                [
                                    'key' => 'field_order_essentials_apply_waste',
                                    'label' => 'Apply waste allowance',
                                    'name' => 'apply_waste',
                                    'type' => 'true_false',
                                    'instructions' => 'Almost always leave this OFF. The project area is worked backwards from the boards in the basket, and those board quantities already include the customer\'s waste allowance, so ticking this applies waste twice: a 50m² deck buys 85 boards, which reads back as 55.19m², and that figure is already 50 × 1.1. Only tick it for a quantity that does NOT derive from the board count.',
                                    'default_value' => 0,
                                    'ui' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'acf-options-product-settings',
                    ],
                ],
            ],
        ]);

        \add_filter('acf/load_field/name=free_sample_disallowed_countries', [__CLASS__, 'load_country_choices']);
    }

    /**
     * Dynamically load country choices when the field is rendered.
     *
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    public static function load_country_choices(array $field): array
    {
        if (!function_exists('WC') || !\WC()->countries instanceof \WC_Countries) {
            return $field;
        }

        $field['choices'] = \WC()->countries->get_countries();

        return $field;
    }
}
