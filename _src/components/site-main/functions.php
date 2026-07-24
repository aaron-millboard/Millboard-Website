<?php

namespace Granola\Components\SiteMain;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'inner_el' => 'div',
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'site-main',
    ], $args['classes']);

    if (!empty($args['object'])) {
        // Use the <article> wrapper on specific post type(s).
        if ($args['object'] instanceof \WP_Post) {
            if (in_array(\get_post_type($args['object']), ['post', 'advice-centre'], true)) {
                $args['inner_el'] = 'article';
            }

            // Add post type class
            $args['classes'][] = 'site-main--' . \get_post_type($args['object']);
        }

        // Display default header if one isn't added in the content.
        $template_page = \Granola\WordPress\TemplatePage::get_template_page($args['object']);

        if (!\is_single() && !empty($template_page)) {
            if (!\has_block('acf/page-header', $template_page) && !\has_block('acf/hero-header', $template_page) && !\has_block('acf/installer-profile-header', $template_page)) {
                $args['header'] = \Granola\Component::get('page-header', [
                    'object' => $args['object'],
                ]);
            }
        } elseif (!\has_block('acf/page-header') && !\has_block('acf/hero-header') && !\has_block('acf/installer-profile-header')) {
            $args['header'] = \Granola\Component::get('page-header', [
                'object' => $args['object'],
            ]);
        }

        if (\is_product()) {
            $attributes = array_keys($args['object']->get_attributes());

            foreach ($attributes as $attribute) {
                $var = \get_query_var('attribute_' . $attribute);


                if (!empty($var)) {
                    $args['attributes']['data-' . $attribute] = $var;
                }
            }

            // Alt. for board width:
            $product = \Theme\PostTypes\Product::get_product_by_sku(\get_query_var('sku'));

            if (!empty($product)) {
                $board_width_attribute_name = \get_field('product_board_width_taxonomy', 'options');
                $board_width = $product->get_attribute($board_width_attribute_name ?? 'pa_board-width');

                if (!empty($board_width)) {
                    $args['attributes']['data-pa_board-width'] = $board_width;
                }
            }
        }

        if (empty($args['id']) && empty($args['attributes']['id'])) {
            $args['attributes']['id'] = 'main';
        }
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
