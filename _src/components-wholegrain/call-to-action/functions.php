<?php

namespace Granola\Components\CallToAction;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'heading' => '',
        'heading_inset' => '',
        'subheading' => '',
        'content' => '',
        'buttons'  => [],
        'image' => null
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'call-to-action',
        'wp-block',
    ], $args['classes']);

    if (!empty($args['buttons'])) {
        foreach ($args['buttons'] as $index => $button) {
            $args['buttons'][$index]['button']['classes'] = 'g-button';
        }
    }

    // -------------------------------------------------------------------------
    // Heading inset.
    // -------------------------------------------------------------------------
    if (!empty($args['heading']) && !empty($args['heading_inset'])) {
        $args['classes'][] = 'call-to-action--has-heading-inset';
        $args['heading'] = \Granola\Component::get('element', [
            'el' => 'span',
            'content' => $args['heading'],
            'classes' => ['call-to-action__heading-primary'],
        ]) . \Granola\Component::get('element', [
            'el' => 'span',
            'content' => $args['heading_inset'],
            'classes' => ['call-to-action__heading-secondary'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
