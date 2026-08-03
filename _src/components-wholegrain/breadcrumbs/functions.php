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
function granola_yoast_breadcrumb_variable_product($link, $index)
{
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

    if (!$product->is_type('variable')) {
        return $link;
    }

    $title = $product->get_name();

    // Get the selected attribute values for 'colour'...
    $colour_attribute_name = \get_field('product_colour_taxonomy', 'options');
    $colour = $product->get_attribute($colour_attribute_name ?? 'pa_colour');

    // ...and 'board-width'
    $board_width_attribute_name = \get_field('product_board_width_taxonomy', 'options');
    $board_width = $product->get_attribute($board_width_attribute_name ?? 'pa_board-width');

    if (!empty($colour) && !empty($board_width)) {
        $divider = ' - ';
    } else {
        $divider = '';
    }

    $replacement = trim("{$colour}{$divider}{$board_width}");
    if ($replacement === '') {
        return $link;
    }

    // get $link content without HTML tags
    $link_content = strip_tags($link);
    $link_content = html_entity_decode($link_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Trim whitespace and remove special characters and unicode characters to ensure accurate comparison
    $link_content = trim(preg_replace('/[^A-Za-z0-9\s]/', '', $link_content));
    $title = trim(preg_replace('/[^A-Za-z0-9\s]/', '', $title));

    if ($link_content === $title) {
        $link = "<span class=\"breadcrumb_last\" aria-current=\"page\">{$replacement}</span>";
        return $link;
    }

    return $link;
}

/**
 * Insert a "Find an Installer" step into the breadcrumb trail on single
 * installer profiles. The installer post type has no archive, so the step is
 * linked to the Find an Installer finder page (resolved per site/locale).
 *
 * @param array $links The breadcrumb links array.
 *
 * @return array
 */
function add_installer_breadcrumb_step(array $links): array
{
    if (!\is_singular('installer')) {
        return $links;
    }

    $finder = \get_page_by_path('find-an-installer');
    $url = $finder ? \get_permalink($finder) : \home_url('/find-an-installer/');

    $crumb = [
        'text' => \__('Find an Installer', 'granola'),
        'url' => $url,
    ];

    // Insert the step just before the current-page (final) crumb.
    if (count($links) > 1) {
        $last = array_pop($links);
        $links[] = $crumb;
        $links[] = $last;
    } else {
        $links[] = $crumb;
    }

    return $links;
}

/**
 * Insert a "Find a Distributor" step into the breadcrumb trail on single
 * distributor profiles. Like installers, the distributor post type has no archive,
 * so the step is linked to the Find a Distributor finder page (resolved per
 * site/locale).
 *
 * @param array $links The breadcrumb links array.
 *
 * @return array
 */
function add_distributor_breadcrumb_step(array $links): array
{
    if (!\is_singular('distributor')) {
        return $links;
    }

    $finder = \get_page_by_path('find-a-distributor');
    $url = $finder ? \get_permalink($finder) : \home_url('/find-a-distributor/');

    $crumb = [
        'text' => \__('Find a Distributor', 'granola'),
        'url' => $url,
    ];

    // Insert the step just before the current-page (final) crumb.
    if (count($links) > 1) {
        $last = array_pop($links);
        $links[] = $crumb;
        $links[] = $last;
    } else {
        $links[] = $crumb;
    }

    return $links;
}

/**
 * Remove duplicate Yoast breadcrumb links.
 *
 * Also removes earlier duplicates when the current page breadcrumb text appears more than once.
 *
 * @param array $links The breadcrumb links array.
 *
 * @return array
 */
function remove_duplicate_yoast_breadcrumb_links(array $links): array
{
    $normalized_links = [];

    foreach ($links as $index => $link) {
        $url = '';
        if (isset($link['url']) && is_string($link['url'])) {
            $url = untrailingslashit($link['url']);
        }

        $text = '';
        if (isset($link['text']) && is_string($link['text'])) {
            $text = $link['text'];
        }

        $normalized_text = html_entity_decode(wp_strip_all_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized_text = strtolower(trim($normalized_text));

        $normalized_links[$index] = [
            'link' => $link,
            'url' => $url,
            'text' => $normalized_text,
            'signature' => $url . '|' . $normalized_text,
        ];
    }

    $last_text = '';
    foreach (array_reverse($normalized_links) as $normalized_link) {
        if ($normalized_link['text'] !== '') {
            $last_text = $normalized_link['text'];
            break;
        }
    }

    $last_text_count = 0;
    $last_text_last_index = -1;
    if ($last_text !== '') {
        foreach ($normalized_links as $index => $normalized_link) {
            if ($normalized_link['text'] === $last_text) {
                $last_text_count++;
                $last_text_last_index = $index;
            }
        }
    }

    $filtered_links = [];
    $previous_signature = null;

    foreach ($normalized_links as $index => $normalized_link) {
        $signature = $normalized_link['signature'];

        if ($signature === $previous_signature) {
            continue;
        }

        if (
            $last_text_count > 1
            && $normalized_link['text'] === $last_text
            && $index !== $last_text_last_index
        ) {
            continue;
        }

        $filtered_links[] = $normalized_link['link'];
        $previous_signature = $signature;
    }

    return $filtered_links;
}
