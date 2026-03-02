<?php

/**
 * Handles core 'Page' post type related functionality.
 */

namespace Theme\PostTypes;

class Product
{
    protected const SLUG = 'product';

    public static function init(): void
    {
        \add_filter('use_block_editor_for_post_type', [__CLASS__, 'activate_gutenberg_block_editor'], 10, 2);
        \add_filter('register_post_type_args', [__CLASS__, 'filter_register_post_type_args'], 10, 2);
        \add_action('init', [__CLASS__, 'add_rewrite_rules'], 10);
        \add_filter('query_vars', [__CLASS__, 'filter_query_vars']);
        \add_action('rest_api_init', [__CLASS__, 'register_endpoints']);
        \add_filter('granola/scripts/localization', [__CLASS__, 'add_rest_endpoint_localization']);
        \add_action('wp', [__CLASS__, 'preload_thumbnail']);
    }

    public static function filter_register_post_type_args($args, $post_type)
    {
        if ($post_type !== self::SLUG) {
            return $args;
        }

        // Set default gutenberg template.
        $args['template'] = [
            ['acf/wc-single-product'],
        ];

        return $args;
    }

    public static function activate_gutenberg_block_editor($can_edit, $post_type)
    {
        if ($post_type === 'product') {
            return true;
        }

        return $can_edit;
    }

    /**
     * Filter Product rewrite rules so that attributes can be used in URLs.
     *
     * @return void
     */
    public static function add_rewrite_rules()
    {
        \add_rewrite_rule('product/([^/]+)/([^/]+)/?$', 'index.php?product=$matches[1]&attribute_pa_colour=$matches[2]', 'top');
        \add_rewrite_rule('product/([^/]+)/([^/]+)/([^/]+)?$', 'index.php?product=$matches[1]&attribute_pa_colour=$matches[2]&sku=$matches[3]', 'top');
    }

    public static function filter_query_vars($query_vars)
    {
        $query_vars[] = 'sku';
        $query_vars[] = 'attribute_pa_colour';
        return $query_vars;
    }


    public static function get_product_by_sku($sku)
    {
        if (empty($sku)) {
            return null;
        }

        return \wc_get_product(
            \wc_get_product_id_by_sku($sku)
        );
    }

    /**
     * Retrieve a product variation that matches a specific set of attribute values.
     *
     * Returns the first match if multiple products would match.
     *
     * @param integer|\WC_Product $product
     * @param array $attributes
     * @return array|null
     */
    public static function get_product_variation_by_attributes(int|\WC_Product $product, array $attributes = []): ?array
    {
        // Retrieve full Product object.
        if (\is_int($product)) {
            /** @var \WC_Product $product */
            $product = \wc_get_product($product);
        }

        // Bail early - only relevant for variable products.
        if (!$product->is_type('variable')) {
            return $product->get_data();
        }

        // Filter out invalid attributes.
        $valid_attributes = array_keys($product->get_attributes());
        $attributes = array_filter($attributes, function ($attribute_value, $attribute_key) use ($valid_attributes) {
            return in_array($attribute_key, $valid_attributes, true);
        }, \ARRAY_FILTER_USE_BOTH);

        // Bail early - no valid attributes to check.
        if (empty($attributes)) {
            return null;
        }

        $variations = $product->get_available_variations();

        // Search for matching variation, returning the first found.
        $found_product = \array_find($variations, function ($variation) use ($attributes) {
            $matches = true;

            foreach ($attributes as $attribute_key => $attribute_value) {
                if ($variation['attributes']['attribute_' . $attribute_key] !== $attribute_value) {
                    $matches = false;
                    break;
                }
            }

            return $matches;
        });

        if (is_array($found_product)) {
            return $found_product;
        }

        return null;
    }

    public static function register_endpoints(): void
    {
        \register_rest_route('millboard/v1', '/products/get-variation-url', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => '\Theme\PostTypes\Product::get_variation_url',
            'permission_callback' => '__return_true', // TODO: add comprehensive permission callback.
        ]);
    }

    public static function add_rest_endpoint_localization($localizations)
    {
        $localizations['product_variation_url'] = \home_url('/wp-json/millboard/v1/products/get-variation-url');
        return $localizations;
    }

    public static function get_variation_url(\WP_REST_Request $request): \WP_Error|string
    {
        $product_id = (int) $request->get_param('product_id');
        $board_width = $request->get_param('pa_board-width');
        $colour = $request->get_param('pa_colour');

        if (empty($product_id) || empty($colour)) {
            return '';
        }

        $variation = self::get_product_variation_by_attributes($product_id, [
            'pa_board-width' => $board_width,
            'pa_colour' => $colour,
        ]);

        if (empty($variation) || empty($variation['sku'])) {
            return '';
        }

        return \trailingslashit(\get_permalink($product_id) . $colour . '/' . $variation['sku']);
    }

    public static function preload_thumbnail(): void
    {
        // Bail early - not on a product page.
        if (!\is_singular(self::SLUG)) {
            return;
        }

        $thumbnail = \get_the_post_thumbnail_url(\get_post(), '2048x2048');

        if (empty($thumbnail)) {
            return;
        }

        \add_filter('granola/wordpress/head/links', function (array $links) use ($thumbnail): array {
            $links[] = [
                'rel' => 'preload',
                'href' => $thumbnail,
            ];

            return $links;
        });
    }
}
