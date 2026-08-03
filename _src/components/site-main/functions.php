<?php

namespace Granola\Components\SiteMain;

/**
 * Whether the content already supplies its own page heading.
 *
 * When it does, site-main must not also output the default page-header, or the page
 * ends up with two h1 elements. Any new hero or profile-header block has to be added
 * to this list, which is why it is one list rather than a chain of has_block() calls
 * repeated in both branches below.
 *
 * @param int|\WP_Post|null $post Optional post to inspect instead of the current one.
 */
function has_own_header($post = null): bool
{
    $blocks = \apply_filters('granola/components/site-main/header_blocks', [
        'acf/page-header',
        'acf/hero-header',
        'acf/installer-profile-header',
        'acf/distributor-profile-hero',
    ]);

    foreach ($blocks as $block) {
        if ($post === null ? \has_block($block) : \has_block($block, $post)) {
            return true;
        }
    }

    return false;
}

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
            if (!has_own_header($template_page)) {
                $args['header'] = \Granola\Component::get('page-header', [
                    'object' => $args['object'],
                ]);
            }
        } elseif (!has_own_header()) {
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
