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
                    'key' => 'field_order_essentials_matrix_json',
                    'label' => 'Recommendation matrix (JSON)',
                    'name' => 'order_essentials_matrix_json',
                    'type' => 'textarea',
                    'instructions' => 'Provide an array of rules. Example: [{"source_product_ids":[123],"source_category_slugs":["decking"],"target_product_id":456,"residential_multiplier":0.08,"commercial_multiplier":0.1,"rounding":"ceil","reason":"Fixings for board coverage"}]',
                    'rows' => 10,
                    'new_lines' => '',
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
