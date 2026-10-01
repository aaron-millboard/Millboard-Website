<?php

namespace Granola\Components\AdviceProse;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
    ], $args);

    $args['classes'] = array_merge(['advice-prose', 'wp-block'], $args['classes']);

    // -------------------------------------------------------------------------
    // The article itself goes inside, as the blocks it is already made of:
    // headings, paragraphs, lists, tables, images, buttons, the FAQ
    // accordion. Nothing is converted, so nothing is lost, and an editor
    // keeps editing each one as before. Any block is allowed.
    // -------------------------------------------------------------------------
    $args['innerblocks_tag'] = \Granola\Helpers::build_inner_blocks_tag([
        'class' => 'advice-prose__blocks',
        'template' => [
            ['core/paragraph', ['placeholder' => \__('Write the article…', 'granola')]],
        ],
        'templateLock' => false,
    ]);

    return $args;
}
