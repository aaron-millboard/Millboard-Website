<?php

namespace Granola\Components\Breadcrumbs;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (!function_exists('yoast_breadcrumb')) {
        return null;
    }

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'breadcrumbs',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Filter the markup of the Yoast SEO breadcrumb separator.
 *
 * Overrides the separator setting in Yoast's admin settings.
 *
 * @return string The filtered Yoast SEO breadcrumb separator markup.
 */
function alter_yoast_separator_markup(): string
{
    return \Granola\Component::get('element', [
        'classes' => ['breadcrumbs__yoast-separator'],
        'content' => '/',
    ]);
}

/**
 * Filter the class used in the markup for the Yoast breadcrumbs wrapper.
 *
 * @return string The filtered Yoast breadcrumbs wrapper class.
 */
function set_yoast_wrapper_markup_class(): string
{
    return 'breadcrumbs__yoast-wrapper';
}

/**
 * Hide Yoast breadcrumb links that point to the home page.
 *
 * @param string $link       The breadcrumb link HTML markup.
 * @param array  $breadcrumb The breadcrumb link array.
 *
 * @return string
 */
function hide_home_page_breadcrumb_link($link, $breadcrumb): string
{
    if (empty($breadcrumb['url']) || !is_string($breadcrumb['url'])) {
        return $link;
    }

    $home_url = untrailingslashit((string) home_url('/'));
    $breadcrumb_url = untrailingslashit($breadcrumb['url']);

    if ($breadcrumb_url === $home_url) {
        return '';
    }

    return $link;
}

/**
 * Custom Yoast breadcrumb filter for variable products.
 * Displays selected 'pa_colour' and 'pa_board-width' in the breadcrumb.
 */
function granola_yoast_breadcrumb_variable_product($link, $index) {
    if (!is_product()) {
        return $link;
    }
    global $post;
    if (!$post || $post->post_type !== 'product') {
        return $link;
    }
    $product = wc_get_product($post->ID);
    if (!$product) {
        return $link;
    }

    $title = $product->get_name();
    // Get the selected attribute values for 'colour' and 'board-width'
    $colour = $product->get_attribute('colour');
    $board_width = $product->get_attribute('board-width');
    if(!empty($colour) && !empty($board_width)) {
        $divider = ' - ';
    } else {
        $divider = '';
    }

    // get $link content without HTML tags
    $link_content = strip_tags($link);
    $link_content = html_entity_decode($link_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Trim whitespace and remove special characters and unicode characters to ensure accurate comparison
    $link_content = trim(preg_replace('/[^A-Za-z0-9\s]/', '', $link_content));
    $title = trim(preg_replace('/[^A-Za-z0-9\s]/', '', $title));

    if ($link_content === $title) {

        $link = "<span class=\"breadcrumb_last\" aria-current=\"page\">{$colour}{$divider}{$board_width}</span>";
        return $link;
    }
    
    return $link;
}

