<?php

namespace Granola\Components\CategoryHeader;

/**
 * The opening block of a shop category page.
 *
 * Deliberately not a mode on page-header: that block renders 576 published
 * pages on en-gb alone, so it cannot be reshaped to suit this template.
 */
function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'trail' => [],
        'intro' => '',
        'buttons' => [],
        'image_id' => 0,
        'object' => \Granola\WordPress\PageObject::get(),
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'category-header',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    $term = ($args['object'] instanceof \WP_Term && $args['object']->taxonomy === 'product_cat')
        ? $args['object']
        : null;

    // -------------------------------------------------------------------------
    // Fall back to the category being viewed, so the block is useful before an
    // editor has filled anything in.
    // -------------------------------------------------------------------------
    if (empty($args['heading']) && !empty($term)) {
        $args['heading'] = $term->name;
    }

    if (empty($args['intro']) && !empty($term)) {
        $args['intro'] = $term->description;
    }

    if (empty($args['trail'])) {
        $args['trail'] = build_trail($term);
    }

    // -------------------------------------------------------------------------
    // Bail early if there is nothing to head the page with.
    // -------------------------------------------------------------------------
    if (empty($args['heading'])) {
        return null;
    }

    $args['buttons'] = normalise_buttons($args['buttons']);

    return $args;
}

/**
 * The trail shown above the heading, for example "Shop / Decking".
 *
 * The current page is not included: it is the heading directly below.
 *
 * @param \WP_Term|null $term The category being viewed.
 * @return array<array{label:string,url:string}> The trail.
 */
function build_trail(?\WP_Term $term): array
{
    $shop_id = \function_exists('wc_get_page_id') ? \wc_get_page_id('shop') : 0;

    $trail = [];

    if ($shop_id > 0) {
        $trail[] = [
            'label' => \get_the_title($shop_id),
            'url' => (string) \get_permalink($shop_id),
        ];
    }

    if (empty($term)) {
        return $trail;
    }

    // Ancestors, outermost first. get_ancestors returns nearest first.
    $ancestors = array_reverse(\get_ancestors($term->term_id, 'product_cat', 'taxonomy'));

    foreach ($ancestors as $ancestor_id) {
        $ancestor = \get_term($ancestor_id, 'product_cat');

        if (empty($ancestor) || \is_wp_error($ancestor)) {
            continue;
        }

        $trail[] = [
            'label' => $ancestor->name,
            'url' => (string) \get_term_link($ancestor),
        ];
    }

    return $trail;
}

/**
 * Give each button its style class, primary first.
 *
 * @param array $buttons The button rows.
 * @return array The buttons ready for the link component.
 */
function normalise_buttons(array $buttons): array
{
    $normalised = [];

    foreach ($buttons as $index => $button) {
        $link = $button['link'] ?? $button;

        if (empty($link['url']) || empty($link['title'])) {
            continue;
        }

        $style = $button['style'] ?? ($index === 0 ? 'primary' : 'secondary');

        $normalised[] = [
            'url' => $link['url'],
            'title' => $link['title'],
            'target' => $link['target'] ?? '',
            'classes' => ['g-button', 'g-button--' . $style, 'category-header__button'],
        ];
    }

    return $normalised;
}
