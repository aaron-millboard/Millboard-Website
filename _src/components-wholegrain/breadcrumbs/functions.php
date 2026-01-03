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
