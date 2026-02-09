<?php

namespace Granola\Components\ProductSamples;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'samples' => [],
    ], $args);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (!\is_singular('product')) {
        return null;
    }

    if (empty($args['samples'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'product-samples',
    ], $args['classes']);

    $args['samples'] = array_map(function ($sample) {
        $product = $sample['product'] ?? null;

        if (empty($product)) {
            return null;
        }

        return [
            'product' => $product,
            'sample_type' => $sample['sample_type'],
        ];
    }, $args['samples']);

    // Remove empty sample data.
    $args['samples'] = array_filter($args['samples']);

    if (empty($args['samples'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
