<?php

namespace Granola\Components\GalleryLoop;

function filter_args(array $args): ?array
{
    // ---------------------------------------
    // Default arguments.
    // ---------------------------------------
    $args = array_merge([
        'classes' => [],
        'limit' => 7,
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
        $query_args['tax_query'] = [
            [
                'taxonomy' => 'image_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['image_category']),
            ],
        ];
    }

    $query = new \WP_Query($query_args);
    $image_rows = [];
    $posts_array = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $posts_array[] = \get_post();
        }
        \wp_reset_postdata();
    }

    // Define available patterns (exclude 100 for now)
    $available_patterns = ['50:50', '60:40', '40:60', '70:30', '30:70'];
    shuffle($available_patterns);

    $row_index = 0;
    $post_index = 0;

    // Process first 3 rows with random patterns (6 items)
    for ($i = 0; $i < 3 && $post_index < count($posts_array); $i++) {
        if (empty($available_patterns)) {
            break;
        }

        $pattern = array_shift($available_patterns);
        $pattern_parts = explode(':', $pattern);

        // First image in the row
        if (isset($posts_array[$post_index])) {
            $image_rows[$row_index]['image_1'] = build_image_data(
                $posts_array[$post_index],
                (int)$pattern_parts[0],
                false
            );
            $post_index++;
        }

        // Second image in the row
        if (isset($posts_array[$post_index])) {
            $image_rows[$row_index]['image_2'] = build_image_data(
                $posts_array[$post_index],
                (int)$pattern_parts[1],
                true
            );
            $post_index++;
        }

        $row_index++;
    }

    // Add 7th item as 100% width (if exists)
    if (isset($posts_array[$post_index])) {
        $image_rows[$row_index]['image_1'] = build_image_data(
            $posts_array[$post_index],
            100,
            false
        );
    }

    // ---------------------------------------
    // Set up gallery items args.
    // ---------------------------------------
    $args['gallery_items_args'] = [
        'image_rows' => $image_rows,
        'lightbox' => true,
    ];

    // ---------------------------------------
    // Store images for lightbox.
    // ---------------------------------------
    $args['images'] = [];
    foreach ($image_rows as $row) {
        if (!empty($row['image_1'])) {
            $args['images'][] = $row['image_1'];
        }
        if (!empty($row['image_2'])) {
            $args['images'][] = $row['image_2'];
        }
    }
    $args['total_images'] = count($args['images']);

    // Set data-lightbox attribute
    $args['attributes']['data-lightbox'] = true;

    // ---------------------------------------
    // Set up lightbox configuration.
    // ---------------------------------------
    $args['lighbox_background_color'] = 'mist';
    $args['thumbnail_navigation'] = true;
    $args['total_images_label'] = \__('images', 'granola');

    $args['lighbox_attributes'] = [
        'class' => [
            'gallery__lightbox',
            'has-background',
            'has-' . $args['lighbox_background_color'] . '-background-color'
        ],
        'role' => 'dialog',
        'aria-modal' => 'true',
        'aria-label' => \__('Gallery images', 'granola'),
        'hidden' => 'hidden',
        'aria-hidden' => 'true',
    ];

    $args['lighbox_close_button'] = [
        'classes' => ['gallery__lightbox__close'],
        'content' => \__('Close', 'granola'),
    ];

    $args['controls'] = [];
    if ($args['thumbnail_navigation']) {
        $args['controls']['previous'] = [
            'content' => \__('Previous', 'granola'),
            'visually-hidden-text' => true,
            'classes' => ['gallery__lightbox__control', 'gallery__lightbox__control--previous', 'g-button'],
        ];
        $args['controls']['next'] = [
            'content' => \__('Next', 'granola'),
            'visually-hidden-text' => true,
            'classes' => ['gallery__lightbox__control', 'gallery__lightbox__control--next', 'g-button'],
        ];
    }

    // ---------------------------------------
    // Set up taxonomy filters args.
    // ---------------------------------------
    $args['filters_args'] = [
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
 * @param int $pattern_part The width percentage (50, 60, 40, 70, 30, or 100).
 * @param bool $is_last Whether this is the last image in the row.
 * @return array Image data array.
 */
function build_image_data(\WP_Post $post, int $pattern_part, bool $is_last = false): array
{
    // Get colour from ACF fields
    $colour_override = \get_field('colour_override', $post->ID);
    $colour_taxonomy = \get_field('colour', $post->ID);

    // If colour_taxonomy is a term ID, get the term name
    if (!empty($colour_taxonomy) && is_numeric($colour_taxonomy)) {
        $term = \get_term($colour_taxonomy, 'image_category');
        if ($term && !is_wp_error($term)) {
            $colour_taxonomy = $term->name;
        }
    }

    $caption_secondary = !empty($colour_override) ? $colour_override : $colour_taxonomy;

    // Get featured image
    $image_id = \get_post_thumbnail_id($post->ID);

    // Calculate grid columns using the same logic as gallery component
    $pattern_columns = \Granola\Components\Gallery\pattern_part_to_grid_span($pattern_part, 100, 12, 0, $is_last);

    // Get image orientation
    $orientation = $image_id ? \Granola\Components\Gallery\get_image_orientation($image_id) : '';

    // Get large image for lightbox
    $large_image = \wp_get_attachment_image_src($image_id, 'granola_super');
    $large_image_src = $large_image[0] ?? '';

    // Use a static counter for lightbox index
    static $lightbox_index = 0;
    $current_index = $lightbox_index++;

    return [
        'caption_main' => \get_the_title($post->ID),
        'caption_secondary' => $caption_secondary,
        'image_medium' => [
            'attachment_id' => $image_id,
            'size' => 'full',
        ],
        'image_thumbnail' => [
            'attachment_id' => $image_id,
            'size' => 'thumbnail',
        ],
        'button_attributes' => [
            'class' => ['gallery__card__button'],
            'data-lightbox-index' => $current_index,
            'data-main-image-src' => $large_image_src,
            'aria-label' => (!empty(\get_the_title($post->ID)) ? sprintf('%s: "%s"', \__('View image', 'granola'), \get_the_title($post->ID)) : ''),
            'data-caption-main' => \get_the_title($post->ID),
            'data-caption-secondary' => $caption_secondary,
            'data-image-orientation' => $orientation,
            'aria-haspopup' => 'dialog',
        ],
        'lighbox_button_attributes' => [
            'data-index' => $current_index,
            'aria-label' => \get_the_title($post->ID),
            'aria-current' => $current_index === 0 ? 'true' : 'false',
        ],
        'li_attributes' => [
            'class' => ['gallery__card'],
            'data-pattern-part' => $pattern_part,
            'data-image-orientation' => $orientation,
            'style' => [
                '--gallery--card--column' => $pattern_columns,
            ],
        ],
    ];
}
