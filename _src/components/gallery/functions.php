<?php

namespace Granola\Components\Gallery;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        // 'items' => [],
        'image_rows' => [],
        'images' => [],
        'controls' => [],
        // Config
        'lightbox' => false,
        'thumbnail_navigation' => true,
        'lighbox_background_color' => 'mist',
        'aria_label_prefix' => __('Thumbnail image for', 'granola'),
        'close_button_label' => __('Close', 'granola'),
        'total_images_label' => __('images', 'granola'),
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'gallery',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Bail early - return null for no output.
    // -------------------------------------------------------------------------
    if (empty($args['image_rows'])) {
         return null;
    }

    // -------------------------------------------------------------------------
    // Accessible description.
    // -------------------------------------------------------------------------
    if (!empty($args['accessible_description'])) {
        $args['attributes']['aria-label'] = $args['accessible_description'];
    }

    // -------------------------------------------------------------------------
    // Set controls.
    // -------------------------------------------------------------------------
    if ($args['thumbnail_navigation']) {
        $args['controls']['previous'] = [
            'content' => __('Previous', 'granola'),
            'visually-hidden-text' => true,
            'classes' => ['gallery__lightbox__control', 'gallery__lightbox__control--previous', 'g-button'],
        ];
        $args['controls']['next'] = [
            'content' => __('Next', 'granola'),
            'visually-hidden-text' => true,
            'classes' => ['gallery__lightbox__control', 'gallery__lightbox__control--next', 'g-button'],
        ];
    }

    // -------------------------------------------------------------------------
    // Set lightbox attributes.
    // -------------------------------------------------------------------------
    if ($args['lightbox']) {
        $args['lighbox_attributes'] = [
            'class' => [
                'gallery__lightbox',
                'has-background',
                'has-' . $args['lighbox_background_color'] . '-background-color'
            ],
            'role' => 'dialog',
            'aria-modal' => 'true',
            'aria-label' => $args['accessible_description'] ?? '',
            'hidden' => 'hidden',
            'aria-hidden' => 'true',
        ];

        $args['lighbox_close_button'] = [
            'classes' => ['gallery__lightbox__close'],
            'content' => \Granola\Component::get('element', [
                'content' => $args['close_button_label'],
                'classes' => $args['close_button_label'],
            ]) . \Granola\Component::get('element', [
                'el' => 'span',
                'classes' => ['gallery__lightbox__close__icon'],
                'content' => '',
            ]),
        ];
    }

    // -------------------------------------------------------------------------
    // Process images.
    // -------------------------------------------------------------------------
    $args['images'] = [];
    $image_index = 1;

    // Loop through the image rows.
    foreach ($args['image_rows'] as $key => $image_row) {
        // Get the pattern.
        $pattern = $image_row['pattern'] ?? '50:50';
        $pattern_parts = explode(':', $pattern);
        $image_1 = null;
        $image_2 = null;

        // Process the first image.
        $image_1 = process_image($image_row['image_1'], $image_index, $args, $pattern_parts[0] ?? 100, false);
        $image_index += 1;

        // Process the second image.
        if (isset($image_row['image_2'])) {
            $image_2 = process_image($image_row['image_2'], $image_index, $args, $pattern_parts[1] ?? 100, true);
        }

        // Bail early if no image 1 is returned.
        if (!$image_1) {
            unset($args['image_rows'][$key]);
            continue;
        }

        // Set our processed images to the image rows.
        $image_row_data = [];
        $image_row_data['image_1'] = $image_1;

        // Append first image to the images array.
        $args['images'][] = $image_1;

        // Append second image if it exists to to images array.
        if ($image_2) {
            $image_row_data['image_2'] = $image_2;
            $image_index += 1;
            $args['images'][] = $image_2;
        }

        // Set our image row data.
        $args['image_rows'][$key] = $image_row_data;
    }

    // Collect total images.
    $args['total_images'] = count($args['images']);

    // Set attributes.
    $args['attributes']['data-lightbox'] = $args['lightbox'] ? true : false; // Inits the JS class.
    $args['attributes']['style']['--gallery--items-count'] = $args['total_images'];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Process image.
 * @param array $row The image repeater array.
 * @param int $key The key of the image.
 * @param array $args The arguments array.
 * @param int $pattner_part The width percentage of the image.
 * @param bool $is_last Whether the part is the last part.
 * @return array|false False if no image, otherwise the processed image array.
 */
function process_image(array $row, int $key, array $args, int $pattern_part, bool $is_last = false): array|false
{
    if (empty($row['image']) && empty($row['gallery_image'])) {
        return false;
    }

    if (!empty($row['gallery_image'])) {
        $gallery_post_id = $row['gallery_image'];
        $image_id = get_post_thumbnail_id($gallery_post_id);
        $row['image'] = $image_id;

        if (empty($row['caption_main'])) {
            $row['caption_main'] = get_the_title($gallery_post_id);
        }
    }

    // Collect images and alt.
    $large_image = \wp_get_attachment_image_src($row['image'], 'granola_super');
    $large_image_src = $large_image[0] ?? false;

    // Bail early if no large image.
    if (!$large_image_src) {
        return false;
    }

    $pattern_columns = pattern_part_to_grid_span($pattern_part, 100, 12, 0, $is_last);
    $orientation = get_image_orientation($row['image']);

    // Collect attributes.
    return [
        'image' => $row,
        'pattern_part' => $pattern_part ?? 100,
        'pattern_columns' => $pattern_columns,
        'image_orientation' => $orientation,
        'caption_main' => $row['caption_main'] ?? '',
        'caption_secondary' => $row['caption_secondary'] ?? '',
        'button_attributes' => [
            'class' => ['gallery__card__button'],
            'data-lightbox-index' => $key,
            'data-main-image-src' => $large_image_src,
            'aria-label' => (!empty($row['caption_main']) ? sprintf('%s: "%s"', $args['aria_label_prefix'], $row['caption_main']) : ''),
            'data-caption-main' => $row['caption_main'] ?? '',
            'data-caption-secondary' => $row['caption_secondary'] ?? '',
            'data-image-orientation' => $orientation,
            'aria-haspopup' => 'dialog',
        ],
        'lighbox_button_attributes' => [
            'data-index' => $key,
            'aria-label' => $row['caption_main'] ?? '',
            'aria-current' => $key === 0 ? 'true' : 'false',
            'aria-label' => (!empty($row['caption_main']) ? sprintf('%s: "%s"', $args['aria_label_prefix'], $row['caption_main']) : ''),
        ],
        'image_medium' => [
            'attachment_id' => $row['image'],
            'size' => 'medium_large',
        ],
        'image_thumbnail' => [
            'attachment_id' => $row['image'],
            'size' => 'thumbnail',
        ],
        'li_attributes' => [
            'data-pattern-part' => $pattern_part,
            'data-image-orientation' => $orientation,
            'style' => [
                '--gallery--card--column' => $pattern_columns,
            ],
            'class' => [
                'gallery__card',
            ],
        ],
        // 'aspect_ratio' => $row['aspect_ratio'] ?? '1/1',
    ];
}

/**
 * Convert pattern to grid columns for use in CSS.
 *
 * @param int $part The part to convert.
 * @param int $total The total to convert to.
 * @param int $grid The grid to convert to.
 * @param int $used The used to convert to.
 * @param bool $is_last Whether the part is the last part.
 * @return string The grid columns.
 */
function pattern_part_to_grid_span(int $part, int $total = 100, int $grid = 12, int $used = 0, bool $is_last = false): string
{
    // Full-width row (100)
    if ($part === $total) {
        return '1 / ' . ($grid + 1);
    }

    // Columns this item should occupy
    $columns = (int) round(($part / $total) * $grid);

    // First item starts at line 1
    if (! $is_last) {
        $start = 1;
        $end   = $start + $columns;
    }
    // Last item always ends at grid + 1
    else {
        $end   = $grid + 1;
        $start = $end - $columns;
    }

    return "{$start} / {$end}";
}

/**
 * Determine image orientation: portrait, landscape, or square
 *
 * @param int $attachment_id WordPress attachment ID
 * @return string 'portrait', 'landscape', or 'square'
 */
function get_image_orientation(int $attachment_id): string
{
    // Get attachment metadata
    $meta = \wp_get_attachment_metadata($attachment_id);

    if (! $meta || empty($meta['width']) || empty($meta['height'])) {
        return ''; // Unknown or invalid attachment
    }

    $width  = $meta['width'];
    $height = $meta['height'];

    if ($width > $height) {
        return 'landscape';
    } elseif ($height > $width) {
        return 'portrait';
    } else {
        return 'square';
    }
}
