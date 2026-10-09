<?php

namespace Granola\Components\SiteFooter;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        // brand-5 is Wenge. Set here rather than as a background-color in the
        // stylesheet so the theme's colour context comes with it: the context
        // class is what tells everything inside this footer that it is now on
        // a dark ground, and it also wins over a plain declaration anyway.
        'background_color' => 'brand-5',
        'classes' => [],
        // The brand, not the blog.
        //
        // get_bloginfo('name') returns the internal subsite name, so the
        // copyright line has been reading "EN GB - Residential (c) 2026" on
        // every page of every locale, and "DE-DE - Residential" on the German
        // one. Those names are for the network admin list, not for readers.
        // Millboard is one global brand, so the footer says so.
        'site_name' => 'Millboard',
        'year' => date('Y'),
        'copyright_label' => '',
        'menus' => range(1, 5),
        // The legal strip. A theme location rather than a list of pages,
        // because what counts as legal differs by market: the UK carries two
        // sets of sale terms, the US will not. Unassigned on a subsite, the
        // strip is skipped entirely.
        'legal_menu' => 'footer-legal',
    ], $args);


    $args['copyright_label'] = sprintf(
        // translators: 1: site name. 2: year.
        \__('%1$s © %2$s ', 'granola'),
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
