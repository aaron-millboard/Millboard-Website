<?php

namespace Granola\Components\SiteFooter;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'background_color' => 'brand-2',
        'classes' => [],
        'site_name' => get_bloginfo('name'),
        'year' => date('Y'),
        'copyright_label' => '',
        'wholegrain_label' => sprintf(
            __('A website for people and planet by %s', 'granola'),
            \Granola\Component::get('link', [
                'url' => 'https://wholegraindigital.com',
                'content' => 'Wholegrain',
                'target' => '_blank',
            ]),
        ),
        'menus' => range(1, 5),
    ], $args);


    $args['copyright_label'] = sprintf(
        // translators: 1: site name. 2: year.
        __('%1$s © %2$s ', 'granola'),
        $args['site_name'],
        $args['year'],
    );

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'site-footer',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
