<?php

namespace Granola\Components\SpecPageHeader;

\add_filter('granola/component/spec-page-header', __NAMESPACE__ . '\\filter_args');

// The page already has its own header, so site-main must not draw the default one as well.
\add_filter('granola/components/site-main/header_blocks', static function (array $blocks): array {
    $blocks[] = 'acf/spec-page-header';

    return $blocks;
});

// A body class the block's stylesheet uses to even out the spacing between the blocks of a specifier page.
\add_filter('body_class', static function (array $classes): array {
    if (\is_singular() && \has_block('acf/spec-page-header', \get_the_ID())) {
        $classes[] = 'has-spec-page';
    }

    return $classes;
});
