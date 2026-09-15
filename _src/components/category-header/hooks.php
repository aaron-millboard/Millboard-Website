<?php

namespace Granola\Components\CategoryHeader;

// Component args.
\add_filter('granola/component/category-header', __NAMESPACE__ . '\\filter_args');

// This block supplies its own h1 and breadcrumb, so site-main must not also
// output the default page-header. The theme provides this list for exactly
// that, and doing it from here keeps site-main untouched.
\add_filter('granola/components/site-main/header_blocks', __NAMESPACE__ . '\\register_as_header_block');

/**
 * Declare this block as one that supplies its own page heading.
 *
 * @param array $blocks The block names already declared.
 * @return array The filtered list.
 */
function register_as_header_block(array $blocks): array
{
    $blocks[] = 'acf/category-header';

    return $blocks;
}
