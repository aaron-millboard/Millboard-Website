<?php

namespace Granola\Components\MediaObject;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'size' => 's', // size of the media object (might be large for media-content useage)
        'orientation' => 'vertical', // vertical|horizontal
        'media_position' => 'before', // before|after
        'media_type' => "image", // image|video|illustration
        'background' => null, // todo remove?
        'image_size' => 'medium_large',
        'url' => '',
        'heading_class' => '',
        'heading_level' => 'h2',
        'component_clickable' => true,
        'animate' => false,
        'video' => null,
        'shape' => null,
        'hover_effect' => false,
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'media-object',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Video.
    // -------------------------------------------------------------------------
    if (!empty($args['video'])) {
        $args['video'] = [
            'video' => $args['video'],
        ];

        if (!empty($args['image'])) {
            $args['video']['image'] = $args['image'];
        }

        $args['media_type'] = 'video';
        // $args['classes'][] = 'has-video';
        $args['attributes']['data-media'] = 'image';
        $args['animate'] = false; // Must be false or the transform will break the position fixed on the video.
    } elseif (!empty($args['media'])) {
        // -------------------------------------------------------------------------
        // Image.
        // -------------------------------------------------------------------------
        if (!is_array($args['media'])) {
            // Convert string to array.
            $args['media'] = [
                'attachment_id' => $args['media'],
            ];
        }
        $args['media_type'] = 'image';
        // $args['classes'][] = 'has-image';
        $args['media']['size'] = get_image_size($args['size']);
        $args['attributes']['data-media'] = 'image';
    } elseif (!empty($args['illustration'])) {
        // -------------------------------------------------------------------------
        // Illustration.
        // -------------------------------------------------------------------------
        $args['media_type'] = 'illustration';
        $args['illustration'] = $args['illustration'];
        $args['attributes']['data-media'] = 'illustration';
    }

    // -------------------------------------------------------------------------
    // Link.
    // -------------------------------------------------------------------------
    if (!empty($args['url'])) {
        // $args['classes'][] = 'has-link';
        $args['attributes']['data-link'] = 'true';
    }

    // -------------------------------------------------------------------------
    // Heading.
    // -------------------------------------------------------------------------
    if (!empty($args['heading'])) {
        $args['heading'] = [
            'content' => $args['heading'],
            'classes' => ['media-object__heading'],
            'el' => $args['heading_level']
        ];

        // Add the URL to the heading.
        if (!empty($args['url'])) {
            $args['heading']['link'] = $args['url'];
            $args['heading']['target'] = $args['target'] ?? null;
        }

        // Add any custom classes to the heading.
        if (!empty($args['heading_class'])) {
            $args['heading']['classes'][] = $args['heading_class'];
        }
    }

    // -------------------------------------------------------------------------
    // Buttons.
    // -------------------------------------------------------------------------
    if (!empty($args['buttons'])) {
        // If we have 1 button and no heading link, add a link to heading.
        if (count($args['buttons']) === 1 && empty($args['url']) && $args['component_clickable']) {
            $args['heading']['link'] = $args['buttons'][0]['url'];
            $args['heading']['target'] = $args['buttons'][0]['target'] ?? null;
            $args['attributes']['data-link'] = 'true';
        }
    }

    // Add classes.
    // $args['classes'][] = 'media-object--media-type--' . $args['media_type'];
    // $args['classes'][] = 'media-object--media-position--' . $args['media_position'];
    // $args['classes'][] = 'media-object--layout--' . $args['orientation'];

    // -------------------------------------------------------------------------
    // Background color
    // -------------------------------------------------------------------------
    if (!empty($args['background']) && $args['background'] !== 'none') {
        $args['classes'][] = 'has-' . $args['background'] . '-background-color';
        $args['classes'][] = 'has-background';
    }

    if (!empty($args['media_type'])) {
        $args['attributes']['data-media-type'] = $args['media_type'];
        $args['attributes']['data-media-position'] = $args['media_position'];
    }

    $args['attributes']['data-orientation'] = $args['orientation'];

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}

/**
 * Gets the correct image size for this media object.
 *
 * @param string $size T-Shirt sizes of images.
 * @return string WP Media size.
 */
function get_image_size($size)
{
    return match ($size) {
        's' => 'medium',
        'm' => 'medium_large',
        'l' => 'super',
        default => 'medium_large',
    };
}



/**
 * Filters media object to set the heading level.
 *
 * @param array $args Component args.
 * @param object|\Granola\Partial $partial The instanciated Granola Partial.
 * @return array Component args with addition of heading_level if we're nested.
 */
function set_heading_level($args, $partial)
{
    // Check if we have a parent.
    if (!isset($partial->parent->parent->name)) {
        return $args;
    }

    // Bail if not cards.
    if ($partial->parent->parent->name !== 'cards') {
        return $args;
    }

    $card_args = $partial->parent->parent->args;

    if (isset($card_args['heading']) && !empty($card_args['heading'])) {
        $args['heading_level'] = 'h3';
    }

    return $args;
}
