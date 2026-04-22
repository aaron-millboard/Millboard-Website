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
