<?php

namespace Granola\Components\ProductSamples;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'heading' => 'Hello World',
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'product-samples',
    ], $args['classes']);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['heading'])) {
        return null;
    }

    if (!empty($args['heading'])) {
        $args['heading'] = [
            'content' => $args['heading'],
            'classes' => ['product-samples__heading'],
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
