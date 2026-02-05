<?php

namespace Granola\Components\GTM\Head;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'gtm_id' => \get_field('gtm_id', 'option'),
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (\Granola\Helpers::is_local_environment()) {
        return null;
    }

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['gtm_id'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Adds the component to the relevant action hook where it will be rendered.
 *
 * @return void
 */
function hook_component()
{
    // Priority 100: Add closer to the end of the <head>.
    \add_action('wp_head', \Granola\Component::get('gtm/head'), 100);
}
