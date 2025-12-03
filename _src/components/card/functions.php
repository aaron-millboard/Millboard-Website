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

    // Disable subheading
    $args['subheading'] = '';

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
        $args['subheading'] = \get_the_excerpt($object->ID);

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
        // Set up args for Posts
        // -------------------------------------------------------------------------
        if ($object->post_type === 'journal') {
            // -------------------------------------------------------------
            // Journal specific args
            // -------------------------------------------------------------
            $args['heading_class'] = 'is-style-typestyle-h5';
            $args['config']['show_read_more'] = true;
            $args['config']['read_more_label'] = \__('Read the journal', 'granola');
            $args['labels'] = \Theme\Meta\ObjectMeta::get_object_labels($object->ID, [
                'taxonomies' => ['topic'],
            ]);

             // Example meta.
             $meta_author = \Theme\Meta\ObjectMeta::get_object_author($object);
            if ($meta_date = \Theme\Meta\ObjectMeta::get_object_date($object)) {
                $args['meta'][] = [
                   'content' => $meta_date,
                ];
            }

            if ($meta_author = \Theme\Meta\ObjectMeta::get_object_author($object)) {
                $meta_author['content'] = sprintf(\__('by %s', 'granola'), $meta_author['content']);
                $args['meta'][] = $meta_author;
            }

            $args['orientation'] = 'horizontal';
        } elseif ($object->post_type === 'person') {
            if (!$args['orientation'] === 'vertical') {
                $args['orientation'] = 'horizontal';
            }

            $args['heading_class'] = 'is-style-typestyle-meta';

            if ($job_title = \get_field('job_title', $object->ID)) {
                $args['subheading'] = $job_title;
            }

            if ($location = \get_field('location', $object->ID)) {
                $args['meta'][] = $location;
            }

            if ($person_socials = \get_field('person_socials', $object->ID)) {
                $args['buttons'] = array_map(function ($social) {
                    return [
                        'url' => $social['link'],
                        'target' => '_blank',
                        'content' => ucfirst($social['type']),
                        'classes' => ['g-button--text'],
                    ];
                }, $person_socials);
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
