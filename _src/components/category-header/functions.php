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

    // -------------------------------------------------------------------------
    // Bail early if there is nothing to head the page with.
    // -------------------------------------------------------------------------
    if (empty($args['heading'])) {
        return null;
    }

    $args['buttons'] = normalise_buttons($args['buttons']);

    // Carry the media library's alt text. This image shows the product, so it
    // is not decoration, and the image component treats an empty alt as a
    // presentation role.
    $args['image_alt'] = '';

    if (!empty($args['image_id'])) {
        $args['image_alt'] = (string) \get_post_meta((int) $args['image_id'], '_wp_attachment_image_alt', true);
    }

    return $args;
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
            // The theme has no --primary. Solid is the filled one.
            'classes' => array_merge(
                ['g-button'],
                $style === 'primary' ? ['g-button--solid'] : ['g-button--secondary'],
                ['category-header__button']
            ),
        ];
    }

    return $normalised;
}
