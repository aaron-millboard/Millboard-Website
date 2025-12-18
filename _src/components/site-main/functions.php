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
        if ($args['object'] instanceof \WP_Post && \get_post_type($args['object']) === 'post') {
            $args['inner_el'] = 'article';
        }

        // Display default header if one isn't added in the content.
        $template_page = \Granola\WordPress\TemplatePage::get_template_page($args['object']);

        if (!\is_single() && !empty($template_page)) {
            if (!\has_block('acf/page-header', $template_page) || !\has_block('acf/hero-header', $template_page)) {
                $args['header'] = \Granola\Component::get('page-header', [
                    'object' => $args['object'],
                ]);
            }
        } elseif (!\has_block('acf/page-header') || !\has_block('acf/hero-header')) {
            $args['header'] = \Granola\Component::get('page-header', [
                'object' => $args['object'],
            ]);
        }

        if (empty($args['id']) && empty($args['attributes']['id'])) {
            $args['attributes']['id'] = 'main';
        }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
        return $args;
    }
}
