<?php

namespace Granola\Components\List;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'items' => [],
        'parent_class_name' => null,
        'is_buttons' => false,
        'first_button_is_primary' => false,
    ], $args);

    // Bail early if no items are provided.
    if (empty($args['items'])) {
        return null;
    }

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'list',
        'list-reset--hard',
        'flex-column',
        'flex-column--mq-medium--row',
        ($args['parent_class_name'] ? $args['parent_class_name'] . '__list' : ''),
    ], $args['classes']);


    // Loop over items to generate output.
    foreach ($args['items'] as $key => $item) {
        if (empty($item['content'])) {
            unset($args['items'][$key]);
            continue;
        }

        // Handle classes.
        $classes = $item['classes'] ?? [];

        // Handle buttons.
        if ($args['is_buttons']) {
            // Apply g-button class if not already applied.
            if (!in_array('g-button', $classes)) {
                $classes[] = 'g-button';
            }

            // Apply g-button--secondary class if first button is primary.
            if ($args['first_button_is_primary'] && $key > 0) {
                $classes[] = 'g-button--secondary';
            }
        }

        // Handle is 'link' or 'element'.
        $el = isset($item['url']) ? 'link' : 'element';
        $el_content = [];

        // Build content with optional icon
        $el_content[] = \Granola\Component::get('element', [
            'content' => $item['content'],
            'content_filter' => null,
        ]);

        // Add icon if one is provided.
        if (isset($item['icon']) && !empty($item['icon'])) {
            $el_content[] = \Granola\Component::get('icons', [
                'icon' => $item['icon'],
            ]);
        }

        // Generate component arguments.
        $component_args = [
            'url' => $item['url'] ?? '',
            'target' => $item['target'] ?? '',
            'classes' => $classes,
            'content_filter' => null,
            'content' => implode('', $el_content),
            // Forward any attributes the item carries, so list items can expose
            // data-* hooks. Defaults to empty, so existing callers are unchanged.
            'attributes' => $item['attributes'] ?? [],
        ];

        // Add item to list.
        $args['items'][$key] = [
            'el' => $el,
            'component_args' => $component_args,
            'classes' => [
                'list__item',
                ($args['parent_class_name'] ? $args['parent_class_name'] . '__list__item' : ''),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
