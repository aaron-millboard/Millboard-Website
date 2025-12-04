<?php

namespace Granola\Components\Card;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $defaults = [
        'attributes' => [],
        'classes' => [],
        'type' => '', // Usually post_type.
        // 'background' => 'white',
        // Content.
        'heading' => '',
        'subheading' => '',
        'url' => '',
        'target' => null,
        'meta' => [],
        'labels' => [],
        'buttons' => [],
        'media' => [],
        'orientation' => 'vertical',
        'hover_effect' => true,
        // Display.
        'config' => [
            'show_read_more' => $args['show_read_more'] ?? false,
            'read_more_label' => \__('Read more', 'granola'),
            'image_size' => 'medium_large',
            'heading_class' => 'is-style-typestyle-h4',
        ],
    ];

    // Recursively merge the defaults with the args.
    $args = \Granola\Helpers::array_merge_recursive_distinct($defaults, $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'g-card',
        'animate-element',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Handle WP_Post or WP_Term args.
    // -------------------------------------------------------------------------
    if (!empty($args['object'])) {
        $args = handle_wp_object_args($args, $args['object']);
    }

    // Links.
    if (!empty($args['link'])) {
        $args['url'] = $args['link']['url'] ?? $args['url'];
        $args['target'] = $args['link']['target'] ?? $args['target'];
        $args['config']['read_more_label'] = $args['link']['title'] ?? $args['config']['read_more_label'];
    }

    // Shape.
    if (!empty($args['shape_choices']) && $args['shape_choices'] !== 'none') {
        $args['shape'] = $args['shape_choices'];
    }

    // -------------------------------------------------------------------------
    // Read more button
    // -------------------------------------------------------------------------
    if ($args['config']['show_read_more'] && !empty($args['url'])) {
        $args['buttons'][] = [
            'url' => $args['url'],
            'target' => $args['target'],
            'content' => $args['config']['read_more_label'],
        ];
    }


    // -------------------------------------------------------------------------
    // Set image args if one exists
    // -------------------------------------------------------------------------
    if (!empty($args['media']['attachment_id'])) {
        $args['media']['size'] = $args['config']['image_size'];
    }

    $args['attributes']['data-type'] = $args['type'];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Handle WP_Post or WP_Term args.
 *
 * @param array $args The component args.
 * @param \WP_Post|\WP_Term $object The WordPress post or term object.
 * @return array The filtered component args.
 */
function handle_wp_object_args(array $args, object $object): array
{
    if ($object instanceof \WP_Post) {
        $args['type'] = $object->post_type;

        // -------------------------------------------------------------------------
        // Set up args from WordPress posts
        // -------------------------------------------------------------------------
        $args['heading'] = \get_the_title($object->ID);
        $args['url'] = \get_the_permalink($object->ID);

        // Disable subheading
        $args['subheading'] = '';

        // Featured image.
        if (\has_post_thumbnail($object->ID)) {
            $args['media']['attachment_id'] = \get_post_thumbnail_id($object->ID);
        }

        // Fallback subheading.
        if (!\has_excerpt($object->ID)) {
            if ($page_header_content = \get_field('page_header_content', $object->ID)) {
                $args['subheading'] = $page_header_content;
            }
        }

        // -------------------------------------------------------------------------
        // Set up args for Case Studies
        // -------------------------------------------------------------------------
        if ($object->post_type === 'case-study') {
            // Add subheading from excerpt explicitly for case studies
            $args['subheading'] = \get_the_excerpt($object->ID);

            // Populate categories as labels
            $terms = \get_the_terms($object->ID, 'category');
            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $args['labels'][] = [
                        'content' => $term->name,
                    ];
                }
            }
        }
    } elseif ($object instanceof \WP_Term) {
        // -------------------------------------------------------------------------
        // Set up args for Terms
        // -------------------------------------------------------------------------
        $args['heading'] = $object->name;
        $args['url'] = \get_term_link($object->ID);
        $args['subheading'] = $object->description;
    }

    return $args;
}
