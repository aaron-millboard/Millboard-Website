<?php

namespace Granola;

class Image
{
    /**
     * Return and possibly output an image from the assets directory
     * @param string $name
     * @param array $args
     * @return string
     */
    public static function get(string $name, array $args = []): string
    {
        $image = '';

        $args = array_merge([
            'name'    => $name,
            'alt'     => '',
            'attributes' => [],
            'classes' => [],
            'loading' => 'lazy',
            'width'   => 0,
            'height'  => 0,
        ], $args);

        if ($image_url = self::url($args['name'])) {
            $attributes = array_merge($args['attributes'], [
                'src' => $image_url,
                'alt' => $args['alt'],
                'loading' => $args['loading'],
            ]);

            if ($attributes['alt'] === "") {
                $attributes['role'] = 'presentation';
            }

            if (!empty($args['classes'])) {
                $attributes['class'] = implode(' ', $args['classes']);
            }

            // If width and height attributes have been defined, set them
            if (!empty($args['width'])) {
                $attributes['width'] = $args['width'];
            }

            if (!empty($args['height'])) {
                $attributes['height'] = $args['height'];
            }

            // If width or height attributes have not been set, attempt to get them automatically
            if (!isset($attributes['width']) || !isset($attributes['height'])) {
                $generated_width = 0;
                $generated_height = 0;

                // Get the width and height (getimagesize doesn't cut it for SVG files)
                if (pathinfo($name, PATHINFO_EXTENSION) === 'svg') {
                    $svg_info = \Granola\SVG::info(\Granola\SVG::path($args['name']));

                    if (!empty($svg_info['w'])) {
                        $generated_width = $svg_info['w'];
                    }

                    if (!empty($svg_info['h'])) {
                        $generated_height = $svg_info['h'];
                    }
                } else {
                    $image_info = getimagesize(self::path($args['name']));

                    if (!empty($image_info[0])) {
                        $generated_width = $image_info[0];
                    }

                    if (!empty($image_info[1])) {
                        $generated_height = $image_info[1];
                    }
                }

                // Set the width and height if values have been generated
                if (!empty($generated_width)) {
                    $attributes['width'] = $generated_width;
                }

                if (!empty($generated_height)) {
                    $attributes['height'] = $generated_height;
                }
            }

            $image = '<img ' . \Granola\Helpers::build_attributes($attributes) . '>';
        }

        return $image;
    }


    /**
     * Build the URL for the image
     * @param string $name
     * @return string
     */
    public static function url(string $name): string
    {
        return \Granola\Asset::url('images/' . $name);
    }


    /**
     * Build the path to the the image
     * @param string $name
     * @return string
     */
    public static function path(string $name): string
    {
        return \Granola\Asset::path('images/' . $name);
    }
}
