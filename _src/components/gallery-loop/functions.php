<?php

namespace Granola\Components\GalleryLoop;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'taxonomy_filters_args' => [],
        'limit' => \Theme\PostTypes\Image::ARCHIVE_POSTS_PER_PAGE,
        'lightbox' => true,
    ], $args);

    // ---------------------------------------
    // Required classes.
    // ---------------------------------------
    $args['classes'] = array_merge([
        'gallery-loop',
        'wp-block',
    ], $args['classes']);

    // ---------------------------------------
    // Query for image posts.
    // ---------------------------------------
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

    $query_args = [
        'post_type' => 'image',
        'posts_per_page' => $args['limit'],
        'post_status' => 'publish',
        'paged' => $paged,
    ];

    // Filter by image_category if provided in URL
    if (isset($_GET['image_category']) && !empty($_GET['image_category'])) {
        $terms = explode(' ', sanitize_text_field($_GET['image_category']));

        foreach ($terms as $term) {
            $query_args['tax_query'][] = [
                [
                    'taxonomy' => 'image_category',
                    'field' => 'slug',
                    'terms' => $term,
                ],
            ];
        }

        if (count($terms) > 1) {
            $query_args['tax_query'][]['relation'] = 'AND';
        }
    }

    // Run our query.
    $query = new \WP_Query($query_args);
    $objects = $query->posts;

    // Set up our available patterns.
    $available_patterns = ['50:50', '60:40', '40:60', '70:30', '30:70'];
    shuffle($available_patterns);


    // Break out our objects into rows of 2.
    $rows = array_chunk($objects, 2);

    foreach ($rows as $key => $row) {
        // Get pattern from available patterns
        $pattern = array_shift($available_patterns);

        // Override pattern if we only have one image in the row.
        if (count($row) === 1) {
            $pattern = '100';
        }

        // Build our row data.
        $row_data = [];
        $row_data['pattern'] = $pattern;

        // Build our image 1 data.
        if (isset($row[0])) {
            $row_data['image_1'] = build_image_data($row[0], 50, false);
        }

        // Build our image 2 data.
        if (isset($row[1])) {
            $row_data['image_2'] = build_image_data($row[1], 50, true);
        }

        // Set our row data.
        $rows[$key] = $row_data;
    }

    // Set our rows.
    $args['rows'] = $rows;

    // ---------------------------------------
    // Set up taxonomy filters args.
    // ---------------------------------------
    $args['taxonomy_filters_args'] = [
        'label' => \__('Explore and filter gallery images', 'granola'),
        'taxonomy' => 'image_category',
        'object' => null,
        'show_images' => true,
        'preserve_url' => true,
    ];

    // ---------------------------------------
    // Set up pagination args.
    // ---------------------------------------
    $args['pagination_args'] = [
        'paged' => $paged,
        'max_num_pages' => $query->max_num_pages,
    ];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Build image data for gallery display.
 *
 * @param \WP_Post $post The post object.
 * @return array Image data array.
 */
function build_image_data(\WP_Post $post): array
{
    return [
        'type' => 'gallery-image',
        'image' => false,
        'gallery_image' => $post->ID,
    ];
}
