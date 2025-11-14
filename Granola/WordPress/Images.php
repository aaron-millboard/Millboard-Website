<?php

namespace Granola\WordPress;

class Images
{
    public static function init(): void
    {
        \add_action('after_setup_theme', [__CLASS__, 'register_image_sizes']);
        \add_filter('image_size_names_choose', [__CLASS__, 'set_theme_image_size_names']);
        \add_filter('intermediate_image_sizes_advanced', [__CLASS__, 'remove_unwanted_image_sizes']);
        \add_filter('jpeg_quality', [__CLASS__, 'set_default_jpeg_quality']);
        \add_filter('big_image_size_threshold', [__CLASS__, 'set_max_image_size']);
    }

    public static function register_image_sizes(): void
    {
        \add_theme_support('post-thumbnails');
        \set_post_thumbnail_size(150, 150, true);

        $granola_sizes = \apply_filters('granola/config/image_sizes', []);
        if (!empty($granola_sizes)) {
            foreach ($granola_sizes as $size_id => $size_info) {
                if ($size_info === false) {
                    continue;
                }

                if (empty($size_info[2])) {
                    $size_info[2] = false;
                }

                \add_image_size(
                    $size_id,
                    $size_info[0],
                    $size_info[1],
                    $size_info[2]
                );
            }
        }
    }

    /**
     * Set the nice names for each image.
     *
     * @link https://developer.wordpress.org/reference/hooks/image_size_names_choose/
     */
    public static function set_theme_image_size_names(array $sizes): array
    {
        $granola_sizes = \apply_filters('granola/config/image_sizes', []);
        if (!empty($granola_sizes)) {
            foreach ($granola_sizes as $size_id => $size) {
                if ($size === false) {
                    continue;
                }

                $sizes[$size_id] = ucwords(str_replace('_', ' ', str_replace('granola_', '', $size_id)));
            }
        }

        return $sizes;
    }

    /**
     * Prevent images for unwanted default sizes being generated on upload.
     *
     * @link https://developer.wordpress.org/reference/hooks/intermediate_image_sizes_advanced/
     */
    public static function remove_unwanted_image_sizes(array $sizes): array
    {
        $granola_sizes = \apply_filters('granola/config/image_sizes', []);
        if (!empty($granola_sizes)) {
            foreach ($sizes as $size_id => $size) {
                if (array_key_exists($size_id, $granola_sizes) && $granola_sizes[$size_id] === false) {
                    unset($sizes[$size_id]);
                }
            }
        }

        return $sizes;
    }

    /**
     * Set the default JPEG compression quality on upload.
     *
     * @link https://developer.wordpress.org/reference/hooks/jpeg_quality/
     */
    public static function set_default_jpeg_quality(int $quality): int
    {
        return \apply_filters('granola/config/jpeg_upload_quality', $quality);
    }

    /**
     * Set the threshold (in pixels) at which images will be downscaled.
     *
     * @link https://developer.wordpress.org/reference/hooks/big_image_size_threshold/
     */
    public static function set_max_image_size(int $threshold): int
    {
        return \apply_filters('granola/config/max_image_size', $threshold);
    }
}
