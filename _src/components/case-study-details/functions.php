<?php

namespace Granola\Components\CaseStudyDetails;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'tags' => [],
        'details' => [],
        'heading' => null,
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'case-study-details',
        'wp-block',
    ], $args['classes']);

    // ---------------------------------------
    // Bail early - return null for no output.
    // ---------------------------------------
    if (empty($args['heading']) && empty($args['is_preview'])) {
        return null;
    } elseif (empty($args['heading'])) {
        $args['heading'] = \__('Add case study details heading', 'granola');
    }

    if (\is_singular('case-study')) {
        $terms = \get_the_terms(\get_post(), 'category');

        if (!empty($terms)) {
            $args['tags'] = array_map(function ($term) {
                return [
                    'content' => $term->name,
                    'url' => \get_term_link($term),
                    'classes' => [
                        'case-study-details__tag',
                        'g-tag',
                        'is-interactive',
                    ],
                ];
            }, $terms);
        }
    }

    if (!empty($args['heading'])) {
        $args['heading'] = [
            'content' => $args['heading'],
            'classes' => ['case-study-details__heading'],
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
