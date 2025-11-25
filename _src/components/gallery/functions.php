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
        'images' => [],
        'images_second_row' => [],
        'controls' => [],
        // Config
        'pattern' => 'pattern-1',
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
    if (empty($args['images'])) {
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
    // Set images.
    // -------------------------------------------------------------------------
    foreach ($args['images'] as $key => $image) {
        $processed_image = process_image($image, $key, $args);
        if (!$processed_image) {
            unset($args['images'][$key]);
            continue;
        }

        // Set attributes.
        $args['images'][$key] = $processed_image;
    }


    // Collect total images.
    $args['total_images'] = count($args['images']);

    // Set attributes.
    $args['attributes']['data-lightbox'] = $args['lightbox'] ? true : false; // Inits the JS class.
    $args['attributes']['data-pattern'] = $args['pattern']; // Used to determine the styles.
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
 * @return array|false False if no image, otherwise the processed image array.
 */
function process_image(array $row, int $key, array $args): array|false
{
    if (empty($row['image'])) {
        return false;
    }

    // Collect images and alt.
    $large_image = \wp_get_attachment_image_src($row['image'], 'granola_super');
    $large_image_src = $large_image[0] ?? false;

    // Bail early if no large image.
    if (!$large_image_src) {
        return false;
    }

    // Collect attributes.
    return [
        'image' => $row,
        'caption_main' => $row['caption_main'] ?? '',
        'caption_secondary' => $row['caption_secondary'] ?? '',
        'button_attributes' => [
            'class' => ['gallery__card__button'],
            'data-lightbox-index' => $key,
            'data-main-image-src' => $large_image_src,
            'aria-label' => (!empty($row['caption_main']) ? sprintf('%s: "%s"', $args['aria_label_prefix'], $row['caption_main']) : ''),
            'data-caption-main' => $row['caption_main'] ?? '',
            'data-caption-secondary' => $row['caption_secondary'] ?? '',
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
        // 'aspect_ratio' => $row['aspect_ratio'] ?? '1/1',
    ];
}
