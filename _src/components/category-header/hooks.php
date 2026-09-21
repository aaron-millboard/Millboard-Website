<?php

namespace Granola\Components\CategoryHeader;

// Component args.
\add_filter('granola/component/category-header', __NAMESPACE__ . '\\filter_args');

// This block supplies its own h1 and breadcrumb, so site-main must not also
// output the default page-header. The theme provides this list for exactly
// that, and doing it from here keeps site-main untouched.
\add_filter('granola/components/site-main/header_blocks', __NAMESPACE__ . '\\register_as_header_block');

// Put the Shop level into the trail on a product category archive.
\add_filter('wpseo_breadcrumb_links', __NAMESPACE__ . '\\add_shop_to_breadcrumb');

/**
 * Add the Shop level to a product category breadcrumb.
 *
 * A product reads Home / Shop / Composite Decking / Antique Oak, but the
 * category archive above it drops the Shop level and reads Home / Composite
 * Decking. Scoped to product category archives, so no other trail changes.
 *
 * @param array $links The breadcrumb links.
 * @return array The filtered links.
 */
function add_shop_to_breadcrumb(array $links): array
{
    if (!\is_tax('product_cat') || !\function_exists('wc_get_page_id')) {
        return $links;
    }

    $shop_id = (int) \wc_get_page_id('shop');

    if ($shop_id < 1) {
        return $links;
    }

    $shop_url = (string) \get_permalink($shop_id);
    $shop_text = \get_the_title($shop_id);

    if ($shop_url === '' || $shop_text === '') {
        return $links;
    }

    foreach ($links as $link) {
        if (($link['url'] ?? '') === $shop_url || (int) ($link['id'] ?? 0) === $shop_id) {
            return $links;
        }
    }

    // A url and text pair, not the legacy ['id' => x] form: current Yoast
    // builds its trail from indexables and drops a crumb it cannot resolve,
    // which is why the id form silently produced nothing.
    array_splice($links, 1, 0, [[
        'url' => $shop_url,
        'text' => $shop_text,
    ]]);

    return $links;
}

/**
 * Declare this block as one that supplies its own page heading.
 *
 * @param array $blocks The block names already declared.
 * @return array The filtered list.
 */
function register_as_header_block(array $blocks): array
{
    $blocks[] = 'acf/category-header';

    return $blocks;
}
